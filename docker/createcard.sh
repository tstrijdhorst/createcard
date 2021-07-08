#!/usr/bin/env bash

set -e

cd $( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )

docker run -it --rm -v "${PWD}/../:/createcard" -v "${HOME}/.config/createcard:/home/createcard/.config/createcard/" createcard php createcard.php "$@"
