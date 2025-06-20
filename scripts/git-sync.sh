#!/bin/bash
set -e

echo "🔄 SYNCING LEARNING SCORECARD CHANGES"
echo "====================================="

# Check if learning-scorecard directory exists
if [ ! -d "learning-scorecard-moodle" ]; then
    echo "❌ Learning scorecard directory not found!"
    echo "💡 Make sure you're in the right directory or run the resume script first."
    exit 1
fi

cd learning-scorecard-moodle

# Check if this is a git repository
if [ ! -d ".git" ]; then
    echo "❌ This is not a git repository!"
    echo "💡 Initialize git repository first:"
    echo "   git init"
    echo "   git remote add origin https://github.com/miguelaguiar01/learning-scorecard-moodle.git"
    exit 1
fi

# Check for changes
if git diff --quiet && git diff --staged --quiet; then
    echo "📝 No changes to commit"
else
    echo "📝 Found changes in learning-scorecard"
    
    # Show what changed
    echo "📋 Changes:"
    git status --short
    
    # Prompt for commit message
    echo ""
    read -p "💬 Enter commit message (or press Enter for default): " commit_msg
    if [ -z "$commit_msg" ]; then
        commit_msg="Update learning scorecard - $(date '+%Y-%m-%d %H:%M')"
    fi
    
    # Commit changes
    git add .
    git commit -m "$commit_msg"
    echo "✅ Changes committed locally"
fi

# Push to remote
echo "☁️  Pushing to GitHub..."
git push origin main || git push origin master

echo "✅ SUCCESS! Learning scorecard changes synced to GitHub!"
echo "🔄 Other machines can now pull these changes when they run the resume script."

cd ..
