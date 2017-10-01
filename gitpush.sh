#!/bin/sh

repo="origin"
branch="master"

if [ $# -ne 1 ]; then
    echo "コメントを入力して下さい。"
    exit 1
fi 

echo "add"
git add .
echo "commit"
git commit -m "$1"
echo "push"
git push $repo $branch

exit 1
