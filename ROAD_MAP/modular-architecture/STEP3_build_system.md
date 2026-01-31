# STEP3: Build System

## Goal
Create automated build scripts that compile modules into single-file releases with datetime versioning.

## Components

### 1. PHP Compiler (`build/compiler.php`)
**Lines**: ~200
**Responsibility**: Module compilation logic

**Features**:
- Read manifest.json
- Load modules in order
- Remove duplicate PHP tags
- Preserve configuration (installer)
- Add header/footer
- Validate syntax
- Generate output

**Usage**:
```bash
php compiler.php --component=backupper --output=../releases/v20260131_140530/wuplicator.php
php compiler.php --component=installer --output=../releases/v20260131_140530/installer.php
```

---

### 2. Windows Build Script (`build/build.bat`)
**Lines**: ~80
**Responsibility**: Windows build automation

**Workflow**:
```batch
@echo off
echo ================================================
echo Wuplicator Build System (Windows)
echo ================================================

:: Generate datetime version
for /f "tokens=2-4 delims=/ " %%a in ('date /t') do (set mydate=%%c%%a%%b)
for /f "tokens=1-2 delims=: " %%a in ('time /t') do (set mytime=%%a%%b)
set VERSION=v%mydate%_%mytime%

echo.
echo Build Version: %VERSION%
echo.

:: Create release directory
set RELEASE_DIR=.\..\releases\%VERSION%
if not exist "%RELEASE_DIR%" mkdir "%RELEASE_DIR%"

echo Building Backupper...
php compiler.php --component=backupper --output="%RELEASE_DIR%\wuplicator.php"
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Backupper build failed!
    exit /b 1
)

echo Building Installer...
php compiler.php --component=installer --output="%RELEASE_DIR%\installer.php"
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Installer build failed!
    exit /b 1
)

echo Generating BUILD_INFO.txt...
echo Wuplicator Build Information > "%RELEASE_DIR%\BUILD_INFO.txt"
echo Version: %VERSION% >> "%RELEASE_DIR%\BUILD_INFO.txt"
echo Build Date: %date% %time% >> "%RELEASE_DIR%\BUILD_INFO.txt"
echo Components: wuplicator.php, installer.php >> "%RELEASE_DIR%\BUILD_INFO.txt"

echo.
echo ================================================
echo Build Complete!
echo ================================================
echo.
echo Release: %RELEASE_DIR%
echo   - wuplicator.php
echo   - installer.php
echo   - BUILD_INFO.txt
echo.
pause
```

---

### 3. Unix Build Script (`build/build.sh`)
**Lines**: ~80
**Responsibility**: Linux/Mac build automation

**Workflow**:
```bash
#!/bin/bash
echo "================================================"
echo "Wuplicator Build System (Unix)"
echo "================================================"

# Generate datetime version
VERSION="v$(date +%Y%m%d_%H%M%S)"

echo ""
echo "Build Version: $VERSION"
echo ""

# Create release directory
RELEASE_DIR="../releases/$VERSION"
mkdir -p "$RELEASE_DIR"

echo "Building Backupper..."
php compiler.php --component=backupper --output="$RELEASE_DIR/wuplicator.php"
if [ $? -ne 0 ]; then
    echo "ERROR: Backupper build failed!"
    exit 1
fi

echo "Building Installer..."
php compiler.php --component=installer --output="$RELEASE_DIR/installer.php"
if [ $? -ne 0 ]; then
    echo "ERROR: Installer build failed!"
    exit 1
fi

echo "Generating BUILD_INFO.txt..."
cat > "$RELEASE_DIR/BUILD_INFO.txt" <<EOF
Wuplicator Build Information
Version: $VERSION
Build Date: $(date '+%Y-%m-%d %H:%M:%S')
Components: wuplicator.php, installer.php
EOF

echo ""
echo "================================================"
echo "Build Complete!"
echo "================================================"
echo ""
echo "Release: $RELEASE_DIR"
echo "  - wuplicator.php"
echo "  - installer.php"
echo "  - BUILD_INFO.txt"
echo ""
```

---

### 4. Compiler Implementation

**File**: `build/compiler.php`

```php
<?php
/**
 * Wuplicator Module Compiler
 * 
 * Compiles modular PHP files into single-file releases.
 */

class WuplicatorCompiler {
    private $component;
    private $output;
    private $manifest;
    private $modulesDir;
    
    public function __construct($component, $output) {
        $this->component = $component;
        $this->output = $output;
        $this->modulesDir = dirname(__DIR__) . '/modules/' . $component;
    }
    
    public function compile() {
        echo "Compiling: {$this->component}\n";
        
        // Load manifest
        $this->loadManifest();
        
        // Build file content
        $content = $this->buildHeader();
        
        // Add configuration (installer only)
        if ($this->manifest['preserve_config'] ?? false) {
            $content .= $this->extractConfiguration();
        }
        
        // Add modules
        foreach ($this->manifest['modules'] as $module) {
            echo "  + Loading module: {$module}\n";
            $content .= $this->loadModule($module);
        }
        
        // Add bootstrap
        $content .= "\n" . $this->manifest['web_bootstrap'] . "\n";
        
        // Validate syntax
        $this->validateSyntax($content);
        
        // Write output
        $this->writeOutput($content);
        
        echo "✓ Build complete: {$this->output}\n";
    }
    
    private function loadManifest() {
        $path = $this->modulesDir . '/manifest.json';
        if (!file_exists($path)) {
            throw new Exception("Manifest not found: {$path}");
        }
        $this->manifest = json_decode(file_get_contents($path), true);
    }
    
    private function buildHeader() {
        $name = $this->manifest['name'];
        $version = $this->manifest['version'];
        $date = date('Y-m-d');
        
        return <<<PHP
<?php
/**
 * {$name}
 * 
 * Version: {$version}
 * Build Date: {$date}
 * 
 * This file is auto-generated by the Wuplicator build system.
 * Do not edit manually. See modules/{$this->component}/ for source.
 */


PHP;
    }
    
    private function loadModule($moduleName) {
        $path = $this->modulesDir . '/' . $moduleName;
        if (!file_exists($path)) {
            throw new Exception("Module not found: {$path}");
        }
        
        $content = file_get_contents($path);
        
        // Remove PHP tags (keep only code)
        $content = preg_replace('/<\?php/', '', $content, 1);
        $content = preg_replace('/\?>\s*$/', '', $content);
        
        // Remove docblock at start (will be replaced by build header)
        $content = preg_replace('/^\/\*\*.*?\*\//s', '', $content);
        
        return "\n" . trim($content) . "\n";
    }
    
    private function extractConfiguration() {
        // For installer: extract config from template
        $templatePath = dirname(__DIR__) . '/src/installer.php';
        $template = file_get_contents($templatePath);
        
        // Extract section between config markers
        preg_match('/\/\/ CONFIGURATION.*?(\/\/ INSTALLER CODE.*?$)/s', $template, $matches);
        
        return "\n" . ($matches[0] ?? '') . "\n";
    }
    
    private function validateSyntax($content) {
        // Write to temp file and check syntax
        $tempFile = sys_get_temp_dir() . '/wuplicator_syntax_check.php';
        file_put_contents($tempFile, $content);
        
        exec("php -l {$tempFile} 2>&1", $output, $exitCode);
        unlink($tempFile);
        
        if ($exitCode !== 0) {
            throw new Exception("Syntax error:\n" . implode("\n", $output));
        }
        
        echo "  ✓ Syntax valid\n";
    }
    
    private function writeOutput($content) {
        $dir = dirname($this->output);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($this->output, $content);
        echo "  ✓ Written: " . filesize($this->output) . " bytes\n";
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $options = getopt('', ['component:', 'output:']);
    
    if (empty($options['component']) || empty($options['output'])) {
        echo "Usage: php compiler.php --component=<name> --output=<path>\n";
        exit(1);
    }
    
    try {
        $compiler = new WuplicatorCompiler($options['component'], $options['output']);
        $compiler->compile();
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        exit(1);
    }
}
```

---

## Build Output

### Directory Structure
```
releases/
└── v20260131_140530/
    ├── wuplicator.php      # Compiled backupper (1 file, ~1000 lines)
    ├── installer.php       # Compiled installer (1 file, ~800 lines)
    └── BUILD_INFO.txt      # Build metadata
```

### BUILD_INFO.txt Format
```
Wuplicator Build Information
Version: v20260131_140530
Build Date: 2026-01-31 14:05:30
Components: wuplicator.php, installer.php

Backupper:
  - Modules: 7
  - Lines: 1043
  - Size: 45 KB

Installer:
  - Modules: 7
  - Lines: 856
  - Size: 38 KB

Total Build Time: 2.3 seconds
```

---

## Usage

### Manual Build (Windows)
```cmd
cd build
build.bat
```

### Manual Build (Unix)
```bash
cd build
chmod +x build.sh
./build.sh
```

### Custom Version
```bash
# Specify version manually
VERSION=v20260131_140530
mkdir -p "../releases/$VERSION"
php compiler.php --component=backupper --output="../releases/$VERSION/wuplicator.php"
php compiler.php --component=installer --output="../releases/$VERSION/installer.php"
```

---

## Validation Tests

After build, run validation:

```php
// validate.php
require_once '../releases/v20260131_140530/wuplicator.php';

$wuplicator = new Wuplicator();
assert(method_exists($wuplicator, 'createPackage'));
assert(method_exists($wuplicator, 'parseWpConfig'));

echo "✓ Backupper validation passed\n";
```

---

## Success Criteria

- ✅ Build scripts working (Windows & Unix)
- ✅ Datetime versioning functional
- ✅ Modules compile correctly
- ✅ Syntax validation passes
- ✅ Output files functional
- ✅ BUILD_INFO.txt generated
- ✅ Releases organized by version

---

**Next**: STEP4 - Testing & Documentation
