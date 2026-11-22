#!/bin/bash
# Fix Divergent Branches di Server
# Script untuk menyelesaikan masalah divergent branches di production server

echo "========================================="
echo "Fix Divergent Branches di Server"
echo "========================================="
echo ""

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Step 1: Check current status
echo -e "${YELLOW}Step 1: Checking git status...${NC}"
git status

echo ""
echo -e "${YELLOW}Step 2: Checking local commits...${NC}"
git log --oneline -5

echo ""
echo -e "${YELLOW}Step 3: Fetching latest changes from remote...${NC}"
git fetch origin

echo ""
echo -e "${YELLOW}Step 4: Checking for divergent branches...${NC}"
DIVERGENT=$(git rev-list --left-right --count HEAD...origin/master 2>&1)
echo "Divergent count: $DIVERGENT"

if [ "$DIVERGENT" != "0	0" ]; then
    echo -e "${RED}WARNING: Branches are divergent!${NC}"
    echo ""
    echo -e "${YELLOW}Remote commits (not in local):${NC}"
    git log HEAD..origin/master --oneline
    
    echo ""
    echo -e "${YELLOW}Local commits (not in remote):${NC}"
    git log origin/master..HEAD --oneline
    
    echo ""
    echo -e "${YELLOW}Step 5: Resetting hard to remote master...${NC}"
    echo -e "${RED}WARNING: This will discard all local changes!${NC}"
    read -p "Do you want to continue? (yes/no): " confirm
    
    if [ "$confirm" = "yes" ]; then
        git reset --hard origin/master
        echo -e "${GREEN}Successfully reset to origin/master${NC}"
        
        echo ""
        echo -e "${YELLOW}Step 6: Cleaning untracked files...${NC}"
        git clean -fd
        
        echo ""
        echo -e "${GREEN}Done!${NC}"
        echo ""
        echo -e "${YELLOW}Current status:${NC}"
        git status
        
        echo ""
        echo -e "${YELLOW}Latest commit:${NC}"
        git log --oneline -1
    else
        echo -e "${YELLOW}Cancelled. No changes made.${NC}"
        exit 1
    fi
else
    echo -e "${GREEN}Branches are in sync. No action needed.${NC}"
    echo ""
    echo -e "${YELLOW}Pulling latest changes...${NC}"
    git pull origin master --no-rebase
fi

echo ""
echo "========================================="
echo "Fix completed!"
echo "========================================="

