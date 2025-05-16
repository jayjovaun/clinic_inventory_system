# Clinicz Desktop App

A desktop application for the Clinic Inventory Management System, built with Electron and PHP.

## Prerequisites

- [Node.js](https://nodejs.org/) (v14 or later)
- [XAMPP](https://www.apachefriends.org/) (latest version)
- MySQL database with the clinic_inventory schema imported
- [Visual Studio Build Tools 2022](https://visualstudio.microsoft.com/visual-cpp-build-tools/) (for Windows builds)

## Setup and Installation

1. Clone this repository
2. Install dependencies:
   ```
   npm install
   ```
3. Make sure XAMPP is installed on your system
4. Import the clinic_inventory.sql database into MySQL
5. Start the application:
   ```
   npm start
   ```

## Creating a Desktop Application

To create a clickable desktop application:

1. Build the application for your platform:
   ```
   npm run build:win   # For Windows
   npm run build:mac   # For macOS  
   npm run build:linux # For Linux
   ```

2. The installer will be created in the `dist` folder
   - For Windows: `dist/Clinicz-1.0.0-setup.exe`
   - For macOS: `dist/Clinicz-1.0.0.dmg`
   - For Linux: `dist/Clinicz-1.0.0.AppImage`

3. Run the installer and follow the on-screen instructions:
   - The installer will create desktop shortcuts automatically
   - The application will be added to the Start Menu (Windows) or Applications folder (macOS)

4. After installation, you can launch the app by:
   - Clicking the desktop shortcut
   - Finding it in the Start Menu (Windows)
   - Opening it from the Applications folder (macOS)
   - Running it from the application launcher (Linux)

## Development

To run the application in development mode:

```
npm run dev
```

## Setting up Build Environment for Windows

Before building the application on Windows, ensure you have the proper environment setup:

1. Install Visual Studio Build Tools 2022:
   - Download from [Visual Studio](https://visualstudio.microsoft.com/visual-cpp-build-tools/)
   - During installation, select "Desktop Development with C++" workload
   - Make sure "Windows 10/11 SDK" is selected

2. Set environment variables:
   - The application will automatically set `GYP_MSVS_VERSION=2022` during build
   - You can manually set it in System Properties:
     1. Right-click on "This PC" or "My Computer"
     2. Select "Properties"
     3. Click on "Advanced system settings"
     4. Click on "Environment Variables"
     5. Under "System variables", click "New"
     6. Add `GYP_MSVS_VERSION` with the value `2022`

3. Verify Visual Studio Build Tools are installed correctly:
   - Open Command Prompt and run: `where cl.exe`
   - It should return a path to the compiler in your Visual Studio installation

## Building Distributable

To build the application for distribution:

```
npm run dist
```

This will create platform-specific distributable files in the `dist` directory.

### Windows

```
npm run build:win
```

### macOS

```
npm run build:mac
```

### Linux

```
npm run build:linux
```

## Troubleshooting Build Issues

If you encounter build errors related to Visual Studio tools:

1. Missing Windows SDK:
   - Rerun the Visual Studio installer and ensure Windows SDK is selected
   - Or install Windows SDK separately from Microsoft's website

2. Path issues:
   - Make sure the build tools are in your PATH
   - Typically located at `C:\Program Files (x86)\Microsoft Visual Studio\2022\BuildTools\VC\Tools\MSVC\[version]\bin\Hostx64\x64`

3. Node.js architecture mismatch:
   - Ensure your Node.js architecture (32-bit vs 64-bit) matches your project
   - For Electron projects, 64-bit Node.js is recommended

4. MSBuild errors:
   - Check that Windows SDK is properly installed
   - Ensure your .npmrc file in your home directory has `msvs_version=2022`

## Application Structure

- `main.js` - Main Electron process
- `preload.js` - Preload script for renderer process
- `php-app/` - PHP application files 
- `build/` - Build resources like icons

## Icons

Before building the application, replace the placeholder icons in the `build` directory with your own:

- Windows: `build/icon.ico` (256x256 pixels)
- macOS: `build/icon.icns` (1024x1024 pixels) 
- Linux: `build/icon.png` (512x512 pixels)

## Troubleshooting

### Database Connection Issues

If the application can't connect to the database:
1. Make sure XAMPP is running
2. Verify that MySQL server is running
3. Check the database credentials in `php-app/config/database-config.php`

### PHP Server Issues

If the PHP server fails to start:
1. Make sure PHP is installed with XAMPP
2. Check that the path to PHP in `main.js` is correct for your system

## License

ISC 
