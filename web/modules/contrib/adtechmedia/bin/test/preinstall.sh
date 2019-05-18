#!/usr/bin/env bash
#
# Created by vcernomschi on 10/06/2015
#

source $(dirname $0)/_head.sh

echo "Preinstall npm"

####################################################
### Install dependencies globally if don't exist ###
####################################################
(npm list -g codeclimate-test-reporter --depth=0 || sudo npm install -g codeclimate-test-reporter) &&\
(npm list -g istanbul-combine --depth=0 || sudo npm install istanbul-combine) &&\

###################################################
### Install dependencies locally if don't exist ###
###################################################
(if [ ! -d "node_modules/codelyzer" ]; then sudo npm install codelyzer; fi) &&\
(if [ ! -d "node_modules/tslint-eslint-rules" ]; then sudo npm install tslint-eslint-rules; fi) &&\
(if [ ! -d "node_modules/sync-exec" ]; then sudo npm install sync-exec@^0.6.x; fi) &&\
(if [ ! -d "node_modules/fs-extra" ]; then sudo npm install fs-extra@0.x.x; fi) &&\
(if [ ! -d "node_modules/github" ]; then sudo npm install github; fi) &&\
(if [ ! -d "node_modules/aws-sdk" ]; then sudo npm install aws-sdk; fi) &&\
(if [ ! -d "node_modules/s3" ]; then sudo npm install s3; fi) &&\
(if [ ! -d "node_modules/node-dir" ]; then sudo npm install node-dir; fi)

if [ "$TRAVIS" == "true" ]; then
  ##########################################################################################
  ### Resolving detached HEAD error by attaching HEAD to the `TRAVIS_FROM_BRANCH` branch ###
  ##########################################################################################

  IFS=$'\n' TRAVIS_COMMIT_MESSAGES=($(git log -2 --pretty=%s))

  export TRAVIS_COMMIT_MESSAGE=${TRAVIS_COMMIT_MESSAGES[1]}

  ###########################
  ### Case for merging PR ###
  ###########################
  if [ -n "$(git show-ref refs/heads/${TRAVIS_BRANCH})" ]; then
    export PR_MERGE=true
    echo "branch ${TRAVIS_BRANCH} exists!"
  else
    TRAVIS_FROM_BRANCH="travis_from_branch"
    git branch $TRAVIS_FROM_BRANCH
    git checkout $TRAVIS_FROM_BRANCH
    git fetch origin $TRAVIS_BRANCH
    git checkout -qf FETCH_HEAD
    git branch $TRAVIS_BRANCH
    git checkout $TRAVIS_BRANCH
    git checkout $TRAVIS_FROM_BRANCH
  fi

else
  export TRAVIS_COMMIT_MESSAGE=$(git log -1 --pretty=%s)
fi

#############################################################################################
### Copy package.json again - fixes issue when .gitignore doesn't contain "/package.json" ###
#############################################################################################
cp bin/test/package.json .

node $(dirname $0)/node-scripts/GitDiffWalker.js

sudo chown -R travis:travis "./node_modules"
