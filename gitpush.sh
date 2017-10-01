#!/bin/sh

repo = "origin"
branch = "master"

if [ $# -eq 1 ]; then
    echo "引数1(comment):$1"
else
    echo "コメントを入力して下さい。"
    exit 1
fi 

git add .
git commit -m '$1'
git push $repo $branch

exit 0
