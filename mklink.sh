#!/bin/bash

# Get the current directory name (plugin name)
PLUGIN_NAME=$(basename "$(pwd)")

# Get the current directory full path
SOURCE_PATH_WSL="$(pwd)"

# Function to convert WSL path to Windows path
wsl_to_windows_path() {
    if command -v wslpath &> /dev/null; then
        wslpath -w "$1"
    else
        # Manual conversion if wslpath is not available
        local wsl_path="$1"
        if [[ "$wsl_path" =~ ^/mnt/([a-z])(/.*)?$ ]]; then
            local drive="${BASH_REMATCH[1]^^}"
            local path="${BASH_REMATCH[2]}"
            echo "${drive}:$(echo "$path" | sed 's|/|\\|g')"
        else
            echo "Error: Cannot convert WSL path" >&2
            return 1
        fi
    fi
}

# Function to convert Windows/WSL path to WSL path
convert_to_wsl_path() {
    local path="$1"
    
    # If already a WSL path, return as is
    if [[ "$path" =~ ^/mnt/ ]]; then
        echo "$path"
        return
    fi
    
    # Use wslpath if available
    if command -v wslpath &> /dev/null && [[ "$path" =~ ^\\ || "$path" =~ ^[A-Za-z]: ]]; then
        wslpath -u "$path"
    else
        # Manual conversion
        path="${path%\"}"
        path="${path#\"}"
        
        # Replace backslashes with forward slashes
        path=$(echo "$path" | sed 's|\\|/|g')
        
        # Convert Windows drive letter format to WSL format
        if [[ "$path" =~ ^[A-Za-z]:/ ]]; then
            local drive_letter=$(echo "${path:0:1}" | tr '[:upper:]' '[:lower:]')
            local path_after_drive="${path:2}"
            path="/mnt/$drive_letter$path_after_drive"
        fi
        
        echo "$path"
    fi
}

# Check if path was provided as argument
if [ $# -eq 0 ]; then
    echo "WordPress Plugin Junction Creator"
    echo "================================"
    echo "This creates a junction (directory link) that doesn't require admin rights."
    echo ""
    echo "Enter the target WordPress plugins directory path:"
    echo ""
    echo "⚠️  RECOMMENDED: Use Unix/WSL path style:"
    echo "   /mnt/c/Users/Username/Studio/wordpress/wp-content/plugins"
    echo ""
    echo "Or Windows style: C:\\Users\\Username\\Studio\\wordpress\\wp-content\\plugins"
    echo ""
    read -p "Target path: " TARGET_BASE
else
    TARGET_BASE="$1"
    echo "Using provided path: $TARGET_BASE"
fi

# Convert to WSL path for validation
echo ""
echo "Processing..."
TARGET_BASE_WSL=$(convert_to_wsl_path "$TARGET_BASE")

# Verify the target base directory exists
if [ ! -d "$TARGET_BASE_WSL" ]; then
    echo ""
    echo "❌ Error: Target directory does not exist: $TARGET_BASE_WSL"
    echo ""
    echo "Please verify the path and try again."
    exit 1
fi

# Convert paths to Windows format
SOURCE_PATH_WINDOWS=$(wsl_to_windows_path "$SOURCE_PATH_WSL")
TARGET_PATH_WINDOWS=$(wsl_to_windows_path "$TARGET_BASE_WSL")\\$PLUGIN_NAME
TARGET_PATH_WSL="$TARGET_BASE_WSL/$PLUGIN_NAME"

echo ""
echo "Source: $SOURCE_PATH_WINDOWS"
echo "Target: $TARGET_PATH_WINDOWS"

# Remove existing link/directory if it exists
if [ -e "$TARGET_PATH_WSL" ]; then
    echo ""
    echo "Removing existing target..."
    # First try to remove via Windows to handle junctions properly
    cmd.exe /c rmdir "$TARGET_PATH_WINDOWS" 2>/dev/null || rm -rf "$TARGET_PATH_WSL"
fi

# Create junction using cmd.exe
echo ""
echo "Creating junction (no admin required)..."

# Escape special characters for PowerShell
SOURCE_PATH_ESCAPED=$(echo "$SOURCE_PATH_WINDOWS" | sed "s/'/\`'/g")
TARGET_PATH_ESCAPED=$(echo "$TARGET_PATH_WINDOWS" | sed "s/'/\`'/g")

# Use cmd.exe directly - simpler and more reliable
cmd.exe /c mklink /J "$TARGET_PATH_WINDOWS" "$SOURCE_PATH_WINDOWS"
RESULT=$?

if [ $RESULT -eq 0 ]; then
    echo ""
    echo "✅ Junction created successfully!"
else
    # If it failed, try with PowerShell using proper escaping
    echo "Retrying with PowerShell..."
    powershell.exe -Command "cmd /c mklink /J '${TARGET_PATH_ESCAPED}' '${SOURCE_PATH_ESCAPED}'"
    RESULT=$?
fi

if [ $RESULT -eq 0 ]; then
    echo ""
    echo "✅ Plugin '$PLUGIN_NAME' is now linked!"
    echo ""
    echo "WSL path: $TARGET_PATH_WSL"
    echo "Windows path: $TARGET_PATH_WINDOWS"
    
    # Show the junction info
    if [ -e "$TARGET_PATH_WSL" ]; then
        echo ""
        ls -la "$TARGET_PATH_WSL"
    fi
else
    echo ""
    echo "❌ Junction creation failed."
    echo ""
    echo "Try running this command directly in Command Prompt (cmd.exe):"
    echo "mklink /J \"$TARGET_PATH_WINDOWS\" \"$SOURCE_PATH_WINDOWS\""
    echo ""
    echo "Or create a batch file (create-link.bat) with:"
    echo "@echo off"
    echo "mklink /J \"$TARGET_PATH_WINDOWS\" \"$SOURCE_PATH_WINDOWS\""
    echo "pause"
    exit 1
fi