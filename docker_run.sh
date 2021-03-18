#!/usr/bin/env bash

docker run -it --rm -v "$PWD/.env:/usr/src/createcard/.env" -v "$PWD/trello_alias.yml:/usr/src/createcard/trello_alias.yml" create-card "$@"