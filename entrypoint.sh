#!/bin/sh -l

# if a command fails it stops the execution
set -e

# script fails if trying to access to an undefined variable
set -u

function note()
{
    MESSAGE=$1;

    printf "\n";
    echo "[NOTE] $MESSAGE";
    printf "\n";
}

note "Starts"

PACKAGE_DIRECTORY="$1"
SPLIT_REPOSITORY_ORGANIZATION="$2"
SPLIT_REPOSITORY_NAME="$3"
COMMIT_MESSAGE="$4"
BRANCH="$5"
TAG="$6"
USER_EMAIL="$7"
USER_NAME="$8"
USER_PASSWORD="$9"

# setup git
if test ! -z "$USER_EMAIL"
then
    git config --global user.email "$USER_EMAIL"
fi

if test ! -z "$USER_NAME"
then
    git config --global user.name "$USER_NAME"
fi

if test ! -z "$USER_PASSWORD"
then
    git config --global user.password "$USER_PASSWORD"
fi


CLONE_DIR=$(mktemp -d)
TARGET_DIR=$(mktemp -d)
CLONED_REPOSITORY="https://github.com/$SPLIT_REPOSITORY_ORGANIZATION/$SPLIT_REPOSITORY_NAME.git"
note "Cloning '$CLONED_REPOSITORY' repository "

# clone repository
git clone -- "https://$GITHUB_TOKEN@github.com/$SPLIT_REPOSITORY_ORGANIZATION/$SPLIT_REPOSITORY_NAME.git" "$CLONE_DIR"
ls -la "$CLONE_DIR"

note "Cleaning destination repository of old files"

# We're only interested in the .git directory, move it to $TARGET_DIR and use it from now on.
mv "$CLONE_DIR/.git" "$TARGET_DIR/.git"
rm -rf $CLONE_DIR

ls -la "$TARGET_DIR"

if test ! -z "$COMMIT_MESSAGE"
then
    ORIGIN_COMMIT="https://github.com/$GITHUB_REPOSITORY/commit/$GITHUB_SHA"
    COMMIT_MESSAGE="${COMMIT_MESSAGE/ORIGIN_COMMIT/$ORIGIN_COMMIT}"
else
    COMMIT_MESSAGE=$(git show -s --format=%B "$GITHUB_SHA")
fi

note "Copying contents to git repo"

# copy the package directory including all hidden files to the clone dir
# make sure the source dir ends with `/.` so that all contents are copied (including .github etc)
cp -Ra $PACKAGE_DIRECTORY/. "$TARGET_DIR"

note "Files that will be pushed"

cd "$TARGET_DIR"
ls -la

note "Adding git commit"

git add .
git status

# git diff-index : to avoid doing the git commit failing if there are no changes to be commit
git diff-index --quiet HEAD || git commit --message "$COMMIT_MESSAGE"

note "Pushing git commit"

# --set-upstream: sets the branch when pushing to a branch that does not exist
git push --quiet origin $BRANCH

# push tag if present
if test ! -z "$TAG"
then
    note "Publishing tag: ${TAG}"

    # if tag already exists in remote
    TAG_EXISTS_IN_REMOTE=$(git ls-remote origin refs/tags/$TAG)

    # tag does not exist
    if test -z "$TAG_EXISTS_IN_REMOTE"
    then
        git tag $TAG -m "Publishing tag ${TAG}"
        git push --quiet origin "${TAG}"
    fi
fi