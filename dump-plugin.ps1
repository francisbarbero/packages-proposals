param(
    # Root folder of your project/plugin
    [string]$Root    = "D:\workspace\proposal-and-package-gen-1\app\public\wp-content\plugins\packages-proposals"
,
    # Output file
    [string]$OutFile = "D:\workspace\proposal-and-package-gen-1\app\public\wp-content\plugins\packages-proposals\plugin-code.txt"
)

# Directories to exclude completely
$excludedDirs = @(
    "\vendor\",
    "\node_modules\",
    "\.git\",
    "\.idea\",
    "\.vscode\"
)

# ONLY these extensions will be included
# (So NO pdf, jpg, png, etc.)
$allowedExt = @(
    ".php", ".inc",
    ".js", ".ts", ".jsx", ".tsx",
    ".css", ".scss", ".sass", ".less",
    ".html", ".htm",
    ".json",
    ".md",
    ".xml",
    ".yml", ".yaml",
    ".txt",
    ".ini", ".cfg",
    ".ps1", ".bat", ".cmd",
    ".sh",
    ".sql",
    ".csv"
)

# Clear old output if it exists
if (Test-Path $OutFile) {
    Remove-Item $OutFile
}

Get-ChildItem -Path $Root -Recurse -File |
    Where-Object {
        $path = $_.FullName.ToLower()

        # Exclude vendor and other heavy dirs
        -not ($excludedDirs | Where-Object { $path -like "*$_*" }) -and

        # Only include whitelisted extensions
        ($allowedExt -contains $_.Extension.ToLower())
    } |
    Sort-Object FullName |
    ForEach-Object {
        "========================================" | Out-File $OutFile -Append -Encoding UTF8
        "FILE: $($_.FullName)"                   | Out-File $OutFile -Append -Encoding UTF8
        "========================================" | Out-File $OutFile -Append -Encoding UTF8
        ""                                        | Out-File $OutFile -Append -Encoding UTF8

        # Write file content
        Get-Content $_.FullName -Raw | Out-File $OutFile -Append -Encoding UTF8

        "" | Out-File $OutFile -Append -Encoding UTF8
        "" | Out-File $OutFile -Append -Encoding UTF8
    }
