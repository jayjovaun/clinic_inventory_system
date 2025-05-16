// main.js
const { app, BrowserWindow, ipcMain, dialog, Menu, shell } = require('electron');
const path = require('path');
const isDev = require('electron-is-dev');
const { spawn, exec } = require('child_process');
const fs = require('fs');
const findProcess = require('find-process');
const ps = require('ps-node');
const os = require('os');

// Configure environment variables for Electron build
const configureEnvironment = () => {
  // Set GYP_MSVS_VERSION for Windows builds
  if (process.platform === 'win32') {
    process.env.GYP_MSVS_VERSION = '2022';
    
    // Check and add to PATH if needed
    const msvcPath = 'C:\\Program Files (x86)\\Microsoft Visual Studio\\2022\\BuildTools\\VC\\Tools\\MSVC';
    if (fs.existsSync(msvcPath)) {
      // Find the first version folder
      const versions = fs.readdirSync(msvcPath);
      if (versions.length > 0) {
        const versionFolder = versions[0];
        const binPath = path.join(msvcPath, versionFolder, 'bin', 'Hostx64', 'x64');
        
        if (fs.existsSync(binPath) && !process.env.PATH.includes(binPath)) {
          process.env.PATH = `${binPath};${process.env.PATH}`;
          console.log(`Added Microsoft Build Tools to PATH: ${binPath}`);
        }
      }
    }
    
    // Check for Windows SDK
    const sdkPath = 'C:\\Program Files (x86)\\Windows Kits\\10';
    if (!fs.existsSync(sdkPath)) {
      console.warn('Windows SDK not detected. Build may fail if it is required.');
    }
  }
};

// Run environment configuration
configureEnvironment();

// Store references
let mainWindow;
let phpServer;
let mysqlRunning = false;
let apacheRunning = false;
const PORT = 3000;

// Get the XAMPP directory
const getXamppDir = () => {
  // Default locations based on OS
  if (process.platform === 'win32') {
    const possiblePaths = [
      'C:\\xampp',
      'C:\\Program Files\\xampp',
      'C:\\Program Files (x86)\\xampp',
      'C:\\xamppp'
    ];
    
    for (const dir of possiblePaths) {
      if (fs.existsSync(dir)) {
        return dir;
      }
    }
  } else if (process.platform === 'darwin') {
    return '/Applications/XAMPP/xamppfiles';
  } else {
    return '/opt/lampp';
  }
  
  dialog.showErrorBox(
    'XAMPP Not Found', 
    'Could not locate XAMPP installation. Please install XAMPP first.'
  );
  return null;
};

// Function to check if a service is running
const checkServiceStatus = async (serviceName) => {
  try {
    const processList = await findProcess('name', serviceName);
    return processList.length > 0;
  } catch (err) {
    console.error(`Error checking ${serviceName} status:`, err);
    return false;
  }
};

// Start Apache and MySQL
const startXamppServices = async () => {
  const xamppDir = getXamppDir();
  if (!xamppDir) {
    app.quit();
    return;
  }
  
  try {
    // Check if services are already running
    mysqlRunning = await checkServiceStatus('mysqld');
    apacheRunning = await checkServiceStatus('httpd') || await checkServiceStatus('apache2');
    
    if (!mysqlRunning) {
      console.log('Starting MySQL...');
      if (process.platform === 'win32') {
        // Run MySQL with more explicit command
        const mysqlCommand = `"${xamppDir}\\mysql\\bin\\mysqld.exe" --defaults-file="${xamppDir}\\mysql\\bin\\my.ini" --standalone --console`;
        console.log(`Executing MySQL command: ${mysqlCommand}`);
        exec(mysqlCommand);
        
        // Give MySQL time to start
        console.log('Waiting for MySQL to start...');
        await new Promise(resolve => setTimeout(resolve, 5000));
        
        // Verify MySQL is running
        mysqlRunning = await checkServiceStatus('mysqld');
        if (!mysqlRunning) {
          console.log('MySQL failed to start. Trying alternative method...');
          // Alternative method - use XAMPP control panel command
          if (fs.existsSync(`${xamppDir}\\xampp_start.exe`)) {
            exec(`"${xamppDir}\\xampp_start.exe" mysql`);
            await new Promise(resolve => setTimeout(resolve, 5000));
          }
        }
      } else {
        exec(`${xamppDir}/bin/mysql.server start`);
        await new Promise(resolve => setTimeout(resolve, 3000));
      }
      
      // Final check
      mysqlRunning = await checkServiceStatus('mysqld');
      console.log(`MySQL running status: ${mysqlRunning}`);
    }
    
    if (!apacheRunning) {
      console.log('Starting Apache...');
      if (process.platform === 'win32') {
        exec(`"${xamppDir}\\apache\\bin\\httpd.exe" -f "${xamppDir}\\apache\\conf\\httpd.conf"`);
        await new Promise(resolve => setTimeout(resolve, 2000));
      } else {
        exec(`${xamppDir}/bin/apachectl start`);
        await new Promise(resolve => setTimeout(resolve, 2000));
      }
      apacheRunning = true;
    }
    
    // Start PHP development server for our application
    const phpPath = process.platform === 'win32' 
      ? path.join(xamppDir, 'php', 'php.exe')
      : 'php';
    
    const phpAppPath = path.join(__dirname, 'php-app');
    
    // Ensure the php-app directory exists
    if (!fs.existsSync(phpAppPath)) {
      fs.mkdirSync(phpAppPath, { recursive: true });
    }
    
    // Check if MySQL connection is working before starting PHP server
    if (process.platform === 'win32') {
      const mysqlCheckCmd = `"${xamppDir}\\mysql\\bin\\mysql.exe" -u root -e "SELECT 'MySQL Connection Successful' AS Result;"`;
      try {
        exec(mysqlCheckCmd, (error, stdout, stderr) => {
          if (error) {
            console.error(`MySQL connection test failed: ${error.message}`);
            // Show error dialog and get user choice
            const choice = dialog.showMessageBoxSync({
              type: 'warning',
              title: 'Database Connection Issue',
              message: 'Could not connect to MySQL database. Some features may not work properly.',
              detail: 'Please make sure MySQL is running from the XAMPP Control Panel.',
              buttons: ['Continue anyway', 'Exit'],
              defaultId: 0,
              cancelId: 1
            });
            
            if (choice === 0) {
              // User chose to continue
              startPhpServer();
            } else {
              // User chose to exit
              app.quit();
            }
          } else {
            console.log('MySQL connection test successful');
            startPhpServer();
          }
        });
      } catch (err) {
        console.error('Error running MySQL check:', err);
        startPhpServer();
      }
    } else {
      startPhpServer();
    }
    
    // Helper function to start PHP server
    function startPhpServer() {
      console.log(`Starting PHP server at ${phpAppPath} on port ${PORT}...`);
      
      // First run the path fixing script
      const fixPathsScript = path.join(phpAppPath, 'fix_paths.php');
      if (fs.existsSync(fixPathsScript)) {
        console.log('Running fix paths script...');
        try {
          const fixPathsProcess = spawn(phpPath, [fixPathsScript]);
          
          fixPathsProcess.stdout.on('data', (data) => {
            console.log(`Fix Paths: ${data}`);
          });
          
          fixPathsProcess.stderr.on('data', (data) => {
            console.error(`Fix Paths Error: ${data}`);
          });
          
          fixPathsProcess.on('close', (code) => {
            console.log(`Fix paths script exited with code ${code}`);
            // Continue with adding electron header
            addElectronHeader();
          });
        } catch (error) {
          console.error('Error running fix paths script:', error);
          addElectronHeader();
        }
      } else {
        console.log('Fix paths script not found, skipping');
        addElectronHeader();
      }
      
      // Add electron header to PHP files
      function addElectronHeader() {
        const headerScript = path.join(phpAppPath, 'add_electron_header.php');
        if (fs.existsSync(headerScript)) {
          console.log('Adding electron header to PHP files...');
          try {
            const headerProcess = spawn(phpPath, [headerScript]);
            
            headerProcess.stdout.on('data', (data) => {
              console.log(`Add Header: ${data}`);
            });
            
            headerProcess.stderr.on('data', (data) => {
              console.error(`Add Header Error: ${data}`);
            });
            
            headerProcess.on('close', (code) => {
              console.log(`Add header script exited with code ${code}`);
              // Continue with database check
              runDatabaseCheck();
            });
          } catch (error) {
            console.error('Error adding electron header:', error);
            runDatabaseCheck();
          }
        } else {
          console.log('Add header script not found, skipping');
          runDatabaseCheck();
        }
      }
      
      // Run database check script
      function runDatabaseCheck() {
        const dbCheckScript = path.join(phpAppPath, 'check_database.php');
        if (fs.existsSync(dbCheckScript)) {
          console.log('Running database check script...');
          try {
            const dbCheckProcess = spawn(phpPath, [dbCheckScript]);
            
            dbCheckProcess.stdout.on('data', (data) => {
              console.log(`DB Check: ${data}`);
            });
            
            dbCheckProcess.stderr.on('data', (data) => {
              console.error(`DB Check Error: ${data}`);
            });
            
            dbCheckProcess.on('close', (code) => {
              console.log(`Database check script exited with code ${code}`);
              // Start PHP server regardless of DB check result
              startPhpServerInstance();
            });
            
          } catch (error) {
            console.error('Error running database check script:', error);
            startPhpServerInstance();
          }
        } else {
          console.log('Database check script not found, skipping initialization');
          startPhpServerInstance();
        }
      }
      
      function startPhpServerInstance() {
        phpServer = spawn(phpPath, ['-S', `localhost:${PORT}`, '-t', phpAppPath]);
    
    phpServer.stdout.on('data', (data) => {
      console.log(`PHP server: ${data}`);
    });
    
    phpServer.stderr.on('data', (data) => {
      console.error(`PHP server error: ${data}`);
    });
    
        // Wait for server to start
    setTimeout(() => {
      createWindow();
    }, 2000);
      }
    }
    
  } catch (error) {
    console.error('Error starting XAMPP services:', error);
    dialog.showErrorBox('Error', `Failed to start XAMPP services: ${error.message}`);
    app.quit();
  }
};

// Stop XAMPP services when application closes
const stopXamppServices = () => {
  if (phpServer) {
    console.log('Stopping PHP server...');
    if (process.platform === 'win32') {
      // On Windows, we need to kill the process forcefully
      exec(`taskkill /PID ${phpServer.pid} /F /T`);
    } else {
      phpServer.kill();
    }
  }
  
  const xamppDir = getXamppDir();
  if (xamppDir) {
    if (apacheRunning) {
      console.log('Stopping Apache...');
      if (process.platform === 'win32') {
        exec(`"${xamppDir}\\apache\\bin\\httpd.exe" -k stop`);
      } else {
        exec(`${xamppDir}/bin/apachectl stop`);
      }
    }
    
    if (mysqlRunning) {
      console.log('Stopping MySQL...');
      if (process.platform === 'win32') {
        exec(`"${xamppDir}\\mysql\\bin\\mysqladmin.exe" -u root shutdown`);
      } else {
        exec(`${xamppDir}/bin/mysql.server stop`);
      }
    }
  }
};

// Create the browser window
function createWindow() {
  mainWindow = new BrowserWindow({
    width: 1200,
    height: 800,
    webPreferences: {
      nodeIntegration: false,
      contextIsolation: true,
      preload: path.join(__dirname, 'preload.js')
    },
    icon: path.join(__dirname, 'build', 'icon.ico')
  });

  // Load the index.php file
  mainWindow.loadURL(`http://localhost:${PORT}/index.php`);

  // Open DevTools in development mode
  if (isDev) {
    mainWindow.webContents.openDevTools();
  }

  // Handle external links
  mainWindow.webContents.setWindowOpenHandler(({ url }) => {
    // Open external links in the default browser
    shell.openExternal(url);
    return { action: 'deny' };
  });

  // Create application menu
  const template = [
    {
      label: 'File',
      submenu: [
        {
          label: 'Exit',
          accelerator: 'CmdOrCtrl+Q',
          click: () => app.quit()
        }
      ]
    },
    {
      label: 'View',
      submenu: [
        { role: 'reload' },
        { role: 'forceReload' },
        { type: 'separator' },
        { role: 'zoomIn', accelerator: 'CmdOrCtrl+=' },
        { role: 'zoomOut' },
        { role: 'resetZoom' },
        { type: 'separator' },
        { role: 'togglefullscreen' },
        { type: 'separator' },
        { 
          label: 'Developer Tools',
          accelerator: 'F12',
          click: () => mainWindow.webContents.toggleDevTools()
        }
      ]
    }
  ];
  
  const menu = Menu.buildFromTemplate(template);
  Menu.setApplicationMenu(menu);

  mainWindow.on('closed', () => {
    mainWindow = null;
  });
}

// IPC handlers
ipcMain.handle('print-page', async () => {
  if (!mainWindow) return false;
  
  try {
    const data = await mainWindow.webContents.printToPDF({
      margins: {
        top: 0.4,
        bottom: 0.4,
        left: 0.4,
        right: 0.4
      },
      printBackground: true,
      pageSize: 'A4'
    });
    
    const pdfPath = path.join(app.getPath('documents'), 'clinicz-print.pdf');
    fs.writeFileSync(pdfPath, data);
    
    shell.openPath(pdfPath);
    return true;
  } catch (error) {
    console.error('Failed to print:', error);
    return false;
  }
});

// Copy PHP files during first run
const copyPhpFiles = () => {
  const sourceDir = __dirname;
  const targetDir = path.join(__dirname, 'php-app');
  
  // Ensure the target directory exists
  if (!fs.existsSync(targetDir)) {
    fs.mkdirSync(targetDir, { recursive: true });
  }
  
  // List of PHP files to copy
  const phpFiles = [
    'index.php',
    'login.php',
    'signup.php',
    'dashboard.php',
    'inventory.php',
    'reports.php',
    'functions.php',
    'database.php',
    'auth.php',
    'logout.php',
    'footer.php',
    'clear_notification.php'
  ];
  
  // List of directories to copy
  const directories = [
    'api',
    'includes',
    'models',
    'assets',
    'images',
    'config',
    'js'
  ];
  
  // Copy PHP files
  for (const file of phpFiles) {
    const sourcePath = path.join(sourceDir, file);
    const targetPath = path.join(targetDir, file);
    
    if (fs.existsSync(sourcePath)) {
      fs.copyFileSync(sourcePath, targetPath);
      console.log(`Copied ${file} to php-app directory`);
    } else {
      console.warn(`Warning: ${file} not found in source directory`);
    }
  }
  
  // Copy directories
  for (const dir of directories) {
    const sourcePathDir = path.join(sourceDir, dir);
    const targetPathDir = path.join(targetDir, dir);
    
    if (fs.existsSync(sourcePathDir)) {
      // Create target directory if it doesn't exist
      if (!fs.existsSync(targetPathDir)) {
        fs.mkdirSync(targetPathDir, { recursive: true });
      }
      
      // Copy directory content recursively (simplified approach)
      copyDirRecursively(sourcePathDir, targetPathDir);
      console.log(`Copied ${dir} directory to php-app`);
    } else {
      console.warn(`Warning: ${dir} directory not found in source directory`);
    }
  }
};

// Helper function to copy directories recursively
const copyDirRecursively = (source, target) => {
  // Check if source is a directory
  if (fs.statSync(source).isDirectory()) {
    // Create target directory if it doesn't exist
    if (!fs.existsSync(target)) {
      fs.mkdirSync(target, { recursive: true });
    }
    
    // Get all files and subdirectories in the source directory
    const entries = fs.readdirSync(source);
    
    // Copy each entry
    for (const entry of entries) {
      const sourcePath = path.join(source, entry);
      const targetPath = path.join(target, entry);
      
      copyDirRecursively(sourcePath, targetPath);
    }
  } else {
    // It's a file, copy it directly
    fs.copyFileSync(source, target);
  }
};

// App initialization
app.on('ready', async () => {
  // Copy PHP files to php-app directory during first run
  copyPhpFiles();
  
  // Create desktop shortcut (only in production mode)
  if (!isDev) {
    createDesktopShortcut();
  }
  
  // Start XAMPP services and create window
  await startXamppServices();
});

// Quit when all windows are closed
app.on('window-all-closed', () => {
  stopXamppServices();
  if (process.platform !== 'darwin') {
    app.quit();
  }
});

app.on('activate', () => {
  if (BrowserWindow.getAllWindows().length === 0) {
    createWindow();
  }
});

// Handle app quitting
app.on('will-quit', () => {
  stopXamppServices();
});

// Add a single instance lock to prevent multiple instances
const gotTheLock = app.requestSingleInstanceLock();
if (!gotTheLock) {
  app.quit();
} else {
  app.on('second-instance', () => {
    if (mainWindow) {
      if (mainWindow.isMinimized()) mainWindow.restore();
      mainWindow.focus();
    }
  });
}

// Create desktop shortcut (Windows only)
const createDesktopShortcut = () => {
  if (process.platform === 'win32') {
    try {
      // Use node-windows to create a shortcut
      const nodeWindows = require('node-windows');
      
      const exePath = process.execPath;
      const appName = 'Clinicz';
      const desktopPath = path.join(os.homedir(), 'Desktop');
      const shortcutPath = path.join(desktopPath, `${appName}.lnk`);
      
      // Check if shortcut already exists
      if (!fs.existsSync(shortcutPath)) {
        console.log('Creating desktop shortcut...');
        
        // Create the shortcut
        const shortcut = new nodeWindows.Shortcut();
        shortcut.path = exePath;
        shortcut.workingDirectory = path.dirname(exePath);
        shortcut.description = 'Clinicz Desktop Application';
        shortcut.target = exePath;
        shortcut.windowStyle = 1; // Normal window
        shortcut.icon = path.join(__dirname, 'build', 'icon.ico');
        
        // Save the shortcut to the desktop
        shortcut.create(shortcutPath, (err) => {
          if (err) {
            console.error('Failed to create shortcut:', err);
          } else {
            console.log('Desktop shortcut created successfully');
          }
        });
      } else {
        console.log('Desktop shortcut already exists');
      }
    } catch (error) {
      console.error('Error creating desktop shortcut:', error);
    }
  }
};