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

REM Set your GitHub repository URL here (EDIT THIS)
set REPO_URL=https://github.com/akbarranak/Category-404-Redirector.git

REM Check if we're in a git repository
git rev-parse --is-inside-work-tree >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo Initializing new git repository...
    git init
    
    echo Setting up remote repository...
    git remote add origin %REPO_URL%
    
    echo Creating main branch...
    git branch -M main
) else (
    echo Git repository already initialized.
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
    echo Error: Failed to commit changes.
    echo This might be because git needs your name and email configured.
    
    echo Setting up git configuration...
    set /p GIT_NAME=Enter your name: 
    set /p GIT_EMAIL=Enter your email: 
    
    git config user.name "%GIT_NAME%"
    git config user.email "%GIT_EMAIL%"
    
    echo Trying commit again...
    git commit -m "%COMMIT_MESSAGE%"
    if %ERRORLEVEL% NEQ 0 (
        echo Error: Failed to commit changes again.
        pause
        exit /b 1
    )
)

REM Push to remote
echo Pushing to GitHub...
git push -u origin main
if %ERRORLEVEL% NEQ 0 (
    echo First push failed. You may need to authenticate.
    echo Please enter your GitHub credentials when prompted.
    
    REM Try again with credential prompt
    git push -u origin main
    if %ERRORLEVEL% NEQ 0 (
        echo Error: Failed to push to GitHub.
        echo This could be due to:
        echo - No internet connection
        echo - Authentication issues
        echo - Remote branch protection
        pause
        exit /b 1
    )
)

echo Success! Your files have been uploaded to GitHub.
pause