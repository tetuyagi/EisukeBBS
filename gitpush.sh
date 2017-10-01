#!/bin/sh

repo = "origin"
branch = "master"

if [ $# -ne 1 ]; then
    echo "コメントを入力して下さい。"
    exit 1
fi 

git add .
git commit -m $1
git push $repo $branch

exit 0
