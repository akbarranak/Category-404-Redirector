@echo off
echo GitHub Upload Utility
echo --------------------

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

REM Set up git configuration if needed
git config --get user.name >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo Setting up git user information...
    set /p GIT_NAME=Enter your name for git commits: 
    set /p GIT_EMAIL=Enter your email for git commits: 
    
    git config --local user.name "%GIT_NAME%"
    git config --local user.email "%GIT_EMAIL%"
    echo Git user configured.
) else (
    echo Git user already configured.
)

REM Check if we're in a git repository
git rev-parse --is-inside-work-tree >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo Initializing new git repository...
    git init
    
    echo Setting up remote repository...
    git remote add origin %AUTH_URL%
    
    echo Creating main branch...
    git branch -M main
    set BRANCH_NAME=main
) else (
    echo Git repository already initialized.
    
    REM Update remote URL with authentication
    echo Updating remote URL with authentication...
    git remote set-url origin %AUTH_URL%
    
    REM Get current branch name
    for /f "tokens=*" %%a in ('git rev-parse --abbrev-ref HEAD') do set BRANCH_NAME=%%a
    echo Current branch: %BRANCH_NAME%
)

REM Add all changes
echo Adding all changes...
git add .

REM Get commit message from user
set /p COMMIT_MESSAGE=Enter commit message (or press Enter for default): 
if "%COMMIT_MESSAGE%"=="" set COMMIT_MESSAGE=Update files

REM Commit changes
echo Committing changes...
git commit -m "%COMMIT_MESSAGE%"
if %ERRORLEVEL% NEQ 0 (
    echo Trying to commit with --allow-empty...
    git commit --allow-empty -m "%COMMIT_MESSAGE%"
    if %ERRORLEVEL% NEQ 0 (
        echo Error: Failed to commit changes.
        pause
        exit /b 1
    )
)

REM Push to remote
echo Pushing to GitHub...
git push -f -u origin %BRANCH_NAME%
if %ERRORLEVEL% NEQ 0 (
    echo Error: Failed to push to GitHub.
    echo This could be due to:
    echo - No internet connection
    echo - Invalid credentials
    echo - Repository doesn't exist
    echo - Other GitHub API issues
    pause
    exit /b 1
)

echo Success! Your files have been uploaded to GitHub.
echo Repository: https://github.com/%GITHUB_USERNAME%/%REPO_NAME%
echo Branch: %BRANCH_NAME%
pause