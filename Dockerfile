FROM php:7.4-cli
COPY . /usr/src/createcard
WORKDIR /usr/src/createcard
VOLUME $HOME/.config/createcard/trello_alias.yml
VOLUME $HOME/.config/createcard/.env
CMD ["php", "./createcard.php"]