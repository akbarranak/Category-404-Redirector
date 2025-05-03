@echo off
echo GitHub Upload Utility (Main Branch)
echo ----------------------------------

REM Check if git is installed
where git >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo Error: Git is not installed or not in your PATH.
    pause
    exit /b 1
)

REM Get authentication information
echo Setting up GitHub authentication...
set /p GITHUB_USERNAME=Enter your GitHub username: 
set /p GITHUB_TOKEN=Enter your GitHub personal access token: 

if "%GITHUB_USERNAME%"=="" (
    echo Error: Username cannot be empty.
    pause
    exit /b 1
)

if "%GITHUB_TOKEN%"=="" (
    echo Error: Personal access token cannot be empty.
    pause
    exit /b 1
)

REM Set repository information
set /p REPO_NAME=Enter repository name (e.g., Category-404-Redirector): 
if "%REPO_NAME%"=="" (
    echo Error: Repository name cannot be empty.
    pause
    exit /b 1
)

REM Create authenticated URL
set AUTH_URL=https://%GITHUB_USERNAME%:%GITHUB_TOKEN%@github.com/%GITHUB_USERNAME%/%REPO_NAME%.git

REM Create a test file to ensure we have something to push
echo Creating a test file to verify upload...
echo "This is a test file created on %DATE% at %TIME%" > github-test.txt

REM Check if we're in a git repository
git rev-parse --is-inside-work-tree >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo Initializing new git repository...
    git init
    
    echo Setting up git user information...
    set /p GIT_NAME=Enter your name for git commits: 
    set /p GIT_EMAIL=Enter your email for git commits: 
    
    git config --local user.name "%GIT_NAME%"
    git config --local user.email "%GIT_EMAIL%"
    
    echo Setting up remote repository...
    git remote add origin %AUTH_URL%
) else (
    echo Git repository already initialized.
    
    REM Update remote URL with authentication
    echo Updating remote URL with authentication...
    git remote set-url origin %AUTH_URL%
)

REM Force create and checkout main branch
echo Ensuring we're on the main branch...
git checkout -B main
if %ERRORLEVEL% NEQ 0 (
    echo Error: Failed to create/checkout main branch.
    pause
    exit /b 1
)

REM Check for .gitignore that might be excluding files
if exist .gitignore (
    echo Warning: .gitignore file found. Checking if it's excluding files...
    type .gitignore
    echo.
    echo If your files are listed in .gitignore, they won't be uploaded.
)

REM Add all changes
echo Adding all files to staging...
git add -A
echo.
echo Files being added:
git status --short

REM Commit changes
echo.
echo Committing changes...
git commit -m "Upload files to GitHub"
if %ERRORLEVEL% NEQ 0 (
    echo No changes to commit or commit failed.
    echo Trying to commit with --allow-empty...
    git commit --allow-empty -m "Upload files to GitHub"
    if %ERRORLEVEL% NEQ 0 (
        echo Error: Failed to commit changes.
        pause
        exit /b 1
    )
)

REM Push to remote with detailed output
echo.
echo Pushing to GitHub main branch...
git push -v -f origin main
if %ERRORLEVEL% NEQ 0 (
    echo Error: Failed to push to GitHub.
    echo.
    echo Troubleshooting:
    echo 1. Verify the repository exists at: https://github.com/%GITHUB_USERNAME%/%REPO_NAME%
    echo 2. Check that your personal access token has 'repo' permissions
    echo 3. Try creating the repository on GitHub first if it doesn't exist
    pause
    exit /b 1
)

echo.
echo Success! Your files have been uploaded to GitHub.
echo Repository: https://github.com/%GITHUB_USERNAME%/%REPO_NAME%
echo Branch: main
echo.
echo IMPORTANT: If you still don't see your files on GitHub:
echo 1. Make sure you're looking at the main branch on GitHub
echo 2. Try refreshing the page (press Ctrl+F5)
echo 3. Check if there are any files in this directory that aren't excluded by .gitignore
echo.
echo Current directory contents:
dir /b

pause