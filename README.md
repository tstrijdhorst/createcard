# Createcard
Creates a trello card and a github PR with the same title and links the URLS

## Prerequisites
- PHP >= 7.4
- Composer
- [Github CLI](https://github.com/cli/cli)

## Installing
```
./install.sh
```

## Useful aliasses (ZSH/Bash)
(Make sure you double check the paths)
```
# Createcard
alias cr="php ~/repo/createcard/createcard.php create-card "
# function to create a card and opening it in a new tab in firefox
crf ()
{
  OUTPUT=$(cr $@)

  [ $? -eq 0 ] && firefox --new-tab $OUTPUT || echo $OUTPUT
}
# End Createcard
```
