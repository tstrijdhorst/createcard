#!/usr/bin/env bash

set -e

test -f ./Dockerfile || { echo "Run this from the docker directory"; exit 1; }

docker run -it --rm -v "${PWD}/../:/createcard" -v "${HOME}/.config/createcard:/home/createcard/.config/createcard/" createcard php createcard.php "$@"
