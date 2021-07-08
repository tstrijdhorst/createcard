#!/usr/bin/env bash

set -e

test -f ./Dockerfile || { echo "Run this from the docker directory"; exit 1; }

docker build -t createcard:latest .
docker run -it --rm -v "${PWD}/../:/createcard" -u root createcard composer install

echo "Done. You can now run ./docker/createcard.sh <arguments>"
