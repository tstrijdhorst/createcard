# Createcard
Creates a trello card and a github PR with the same title and links the URLS.

## Prerequisites
- PHP >= 7.4
- Composer
- [Github CLI](https://github.com/cli/cli)

## Installing
Run the following script:
```
./install.sh
```
It will install composer dependencies and copy config file templates to your local user.
Be sure to fill in these configfiles with the required information such as API key etc.

### Helpful links for installing

- You can find your Trello API key [here](https://trello.com/app-key)
- You can find the names and ids of your board, members, lists etc by appending `.json` after your board url. (e.g https://trello.com/b/TTAVI7Ny/ue4-roadmap -> https://trello.com/b/TTAVI7Ny/ue4-roadmap.json)

## Usage

Run the following to see all the options:
```
php create-card.php create-card --help
```

### Useful aliasses (ZSH/Bash)
Make your life easier and add an easy to remember alias to run this application. From now on examples will use these names.

(Make sure you double-check the paths)
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

### Trello Aliases

By default all the names as on trello will be used as identifiers. However some of these name might be cumbersome to type. 
For instance lets say you want to apply a label `Really Long Label My Man` to your card.   

You could use:  
`cr doing "descriptive title" -l "Really Long Label My Man"` 

But it's rather annoying isn't it?

Therefore, there is the option to make aliases for members, lists and labels.

#### How do I alias things?

1. Edit the trello alias yaml file in `~/.config/createcard/trello_alias.yml`
2. Add `alias: <Original Name>` in the right block.

For instance if we wanted to add the incredibly intuitive alias `rllmm` for the label from our earlier example we could add it like so:

```yaml
lists:

members:

labels:
  rllmm: Really Long Label My Man
```

Now we can use it like any other name (also the original label name will still work):  
`cr doing "descriptive title" -l rllmm`

