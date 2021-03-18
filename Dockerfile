FROM php:7.4-cli
COPY . /usr/src/createcard
WORKDIR /usr/src/createcard
VOLUME trello_alias.yml
VOLUME .env
CMD ["php", "./createcard.php"]