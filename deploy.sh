#!/bin/sh

if [ $# -eq 1 ]; then
    echo "引数1(comment):$1"
else
    echo "コメントを入力して下さい。"
    exit 1
fi


./gitpush.sh "$1"

if [ $? -ne 0 ]; then
    echo "gitpush.sh failed."
    exit 1
fi

git-ftp.py

exit 0
