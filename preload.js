// preload.js
const { contextBridge, ipcRenderer } = require('electron');
const os = require('os');
const path = require('path');
const fs = require('fs');

// Set up environment variables for Electron build
const setupEnvironmentVariables = () => {
  // Check if we're on Windows
  if (process.platform === 'win32') {
    // Set GYP_MSVS_VERSION environment variable if not already set
    if (!process.env.GYP_MSVS_VERSION) {
      process.env.GYP_MSVS_VERSION = '2022';
      console.log('Set GYP_MSVS_VERSION to 2022');
    }
    
    // Check for .npmrc file and add msvs_version if needed
    const npmrcPath = path.join(os.homedir(), '.npmrc');
    if (fs.existsSync(npmrcPath)) {
      let npmrcContent = fs.readFileSync(npmrcPath, 'utf8');
      if (!npmrcContent.includes('msvs_version')) {
        npmrcContent += '\nmsvs_version=2022\n';
        fs.writeFileSync(npmrcPath, npmrcContent);
        console.log('Added msvs_version=2022 to .npmrc');
      }
    } else {
      // Create .npmrc file if it doesn't exist
      fs.writeFileSync(npmrcPath, 'msvs_version=2022\n');
      console.log('Created .npmrc with msvs_version=2022');
    }
    
    // Check if build tools are in PATH
    const pathEnv = process.env.PATH || '';
    const buildToolsPath = 'C:\\Program Files (x86)\\Microsoft Visual Studio\\2022\\BuildTools\\VC\\Tools\\MSVC';
    if (!pathEnv.includes(buildToolsPath)) {
      // We don't modify PATH here as it requires elevated privileges
      console.log('Note: Build tools path not found in PATH environment variable');
    }
  }
};

// Run setup
setupEnvironmentVariables();

// Expose safe IPC functionality to the renderer process
contextBridge.exposeInMainWorld('electronAPI', {
  // Print the current page
  printPage: () => ipcRenderer.invoke('print-page'),
  
  // Get app version
  getAppVersion: () => ipcRenderer.invoke('get-app-version'),
  
  // Check if running in development mode
  isDev: () => ipcRenderer.invoke('is-dev')
});

// Inject custom functionality once DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  // Add desktop-specific CSS
  const style = document.createElement('style');
  style.textContent = `
    body {
      margin: 0;
      padding: 0;
      overflow: auto;
    }
    
    /* Scrollbar styling */
    ::-webkit-scrollbar {
      width: 8px;
      height: 8px;
    }
    
    ::-webkit-scrollbar-thumb {
      background-color: #888;
      border-radius: 4px;
    }
    
    ::-webkit-scrollbar-track {
      background-color: #f1f1f1;
    }
    
    /* Print button */
    .print-button {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 9999;
      background-color: #4CAF50;
      color: white;
      border: none;
      border-radius: 50%;
      width: 50px;
      height: 50px;
      cursor: pointer;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .print-button:hover {
      background-color: #45a049;
    }
  `;
  document.head.appendChild(style);
  
  // Add print button to certain pages
  const currentPath = window.location.pathname;
  if (currentPath.includes('reports.php') || 
      currentPath.includes('dashboard.php') || 
      currentPath.includes('inventory.php')) {
    
    const printButton = document.createElement('button');
    printButton.className = 'print-button';
    printButton.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>';
    printButton.title = 'Print this page';
    
    printButton.addEventListener('click', async () => {
      const success = await window.electronAPI.printPage();
      if (success) {
        alert('Document generated and opened');
      } else {
        alert('Failed to generate document');
      }
    });
    
    document.body.appendChild(printButton);
  }
});