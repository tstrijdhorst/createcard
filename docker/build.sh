#!/usr/bin/env bash

set -e


cd $( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )

docker build -t createcard:latest .
docker run -it --rm -v "${PWD}/../:/createcard" -u root createcard composer install

echo "Done. You can now run ./docker/createcard.sh <arguments>"
