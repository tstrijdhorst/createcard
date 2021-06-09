#!/usr/bin/env bash

set -e

composer install
mkdir -p ~/.config/createcard
cp -n .env.dist ~/.config/createcard/.env
cp -n trello_alias.yml.dist ~/.config/createcard/trello_alias.yml

echo "Done, make sure to edit the config files in ~/.config/createcard"
