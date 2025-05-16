// build.js - Build script for Clinicz desktop app
const { exec } = require('child_process');
const fs = require('fs');
const path = require('path');
const os = require('os');

// Configure build environment
const configureBuildEnvironment = () => {
  // Set environment variables for Visual Studio 2022
  process.env.GYP_MSVS_VERSION = '2022';
  
  if (process.platform === 'win32') {
    console.log('Setting up Windows build environment...');
    
    // Check for Visual Studio Build Tools
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
    } else {
      console.warn('Warning: Visual Studio Build Tools not found at expected location.');
      console.log('If build fails, please install Visual Studio Build Tools 2022.');
    }
    
    // Create/update .npmrc in user home directory
    const npmrcPath = path.join(os.homedir(), '.npmrc');
    let npmrcContent = '';
    
    if (fs.existsSync(npmrcPath)) {
      npmrcContent = fs.readFileSync(npmrcPath, 'utf8');
      
      // Check if msvs_version is already set
      if (!npmrcContent.includes('msvs_version=')) {
        npmrcContent += '\nmsvs_version=2022\n';
        fs.writeFileSync(npmrcPath, npmrcContent);
        console.log('Added msvs_version=2022 to .npmrc');
      }
    } else {
      // Create new .npmrc file
      fs.writeFileSync(npmrcPath, 'msvs_version=2022\n');
      console.log('Created .npmrc with msvs_version=2022');
    }
  }
};

// Run environment configuration
configureBuildEnvironment();

// Check if build directory exists, create if not
const buildDir = path.join(__dirname, 'build');
if (!fs.existsSync(buildDir)) {
  fs.mkdirSync(buildDir, { recursive: true });
}

// Ensure we have an icon for the application
const ensureAppIcons = () => {
  const iconPath = path.join(__dirname, 'build', 'icon.ico');
  const defaultIconPath = path.join(__dirname, 'images', 'logo.png');
  
  // Check if the icon file exists, if not create a default one
  if (!fs.existsSync(iconPath)) {
    console.log('Creating default application icon...');
    
    // If we have a logo image, use it as the icon
    if (fs.existsSync(defaultIconPath)) {
      // For simple copying in this script - we'll just copy the PNG
      // In a real scenario, you'd want to convert it to ICO format
      try {
        fs.copyFileSync(defaultIconPath, path.join(__dirname, 'build', 'icon.png'));
        console.log('Default icon created from logo.png');
      } catch (err) {
        console.error('Failed to create default icon:', err);
      }
    } else {
      console.warn('No icon found. A default icon will be used by Electron Builder.');
    }
  }
};

// Run the icon creation function
ensureAppIcons();

// Check if php-app directory exists, create if not
const phpAppDir = path.join(__dirname, 'php-app');
if (!fs.existsSync(phpAppDir)) {
  fs.mkdirSync(phpAppDir, { recursive: true });
}

// Copy PHP files if they don't exist in php-app
const sourceDir = __dirname;
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

// Copy PHP files
console.log('Copying PHP files to php-app directory...');
for (const file of phpFiles) {
  const sourcePath = path.join(sourceDir, file);
  const targetPath = path.join(phpAppDir, file);
  
  if (fs.existsSync(sourcePath) && !fs.existsSync(targetPath)) {
    fs.copyFileSync(sourcePath, targetPath);
    console.log(`Copied ${file} to php-app directory`);
  }
}

// Create directories in php-app if they don't exist
const directories = ['api', 'includes', 'models', 'assets', 'images', 'config', 'js'];
for (const dir of directories) {
  const dirPath = path.join(phpAppDir, dir);
  if (!fs.existsSync(dirPath)) {
    fs.mkdirSync(dirPath, { recursive: true });
    console.log(`Created directory: ${dir}`);
  }
}

// Get build platform from arguments
const args = process.argv.slice(2);
let buildPlatform = '';

if (args.includes('--win')) {
  buildPlatform = '--win';
} else if (args.includes('--mac')) {
  buildPlatform = '--mac';
} else if (args.includes('--linux')) {
  buildPlatform = '--linux';
}

// Run electron-builder
console.log('Running electron-builder...');
const buildCommand = buildPlatform 
  ? `npx electron-builder ${buildPlatform}`
  : 'npx electron-builder';

exec(buildCommand, (error, stdout, stderr) => {
  if (error) {
    console.error(`Error: ${error.message}`);
    return;
  }
  
  if (stderr) {
    console.error(`stderr: ${stderr}`);
  }
  
  console.log(`stdout: ${stdout}`);
  console.log('Build completed successfully!');
}); 