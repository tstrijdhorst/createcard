FROM php:7.4-cli
COPY . /usr/src/createcard
WORKDIR /usr/src/createcard
VOLUME /usr/src/createcard/trello_alias.yml
VOLUME /usr/src/createcard/.env
CMD ["php", "./createcard.php"]