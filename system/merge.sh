#!/usr/bin/env bash
BASEDIR=$(dirname $0)
echo "Script location: ${BASEDIR}"
cd $BASEDIR
cd ..

git branch -D backport-system
git subtree split -P system -b backport-system
git push framework backport-system:develop
