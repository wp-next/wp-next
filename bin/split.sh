#!/usr/bin/env bash

set -e
set -x

CURRENT_BRANCH="8.x"

function split()
{
    SHA1=`./bin/splitsh-lite --prefix=$1`
    git push $2 "$SHA1:refs/heads/$CURRENT_BRANCH" -f
}

function remote()
{
    git remote add $1 $2 || true
}

git pull origin $CURRENT_BRANCH

remote view git@github.com:wp-next/view.git

split 'packages/View' view