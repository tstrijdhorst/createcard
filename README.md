# Createcard
Creates a trello card and a github PR with the same title and links the URLS

## Prerequisites
- PHP7+
- Composer

## Installing
```
./install.sh
```

## Useful aliasses (ZSH/Bash)
(Make sure you double check the paths)
```
# Createcard
alias cr="php ~/repo/createcard/createcard.php create-card "
#   open an url in a new tab in firefox
alias of="xargs -I{} firefox --new-tab {}"
#   function to create a card and opening it in a new tab in firefox
crf ()
{
  cr $@ >/dev/stderr | of
}
# End Createcard
```
