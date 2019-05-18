# Config Token

Allows custom tokens to be stored and exported via configuration manager.

Supported modules:
* Domain
* Token Filter

## Similar modules

* Token Custom: The difference is the storage. Token custom has a custom table
 to store configuration, which means, if we want to deploy custom tokens
 programatically, we need to insert rows into the table, whereas Config Token
 allows you to export and import using Configuration manager.

## Usage

When you install the module, it will create some example tokens, that you can
see under /admin/help/token.
At the moment, there is no UI to manage the custom tokens, but we will prioritise
it. We have started Config Token UI module.

If you want to add your custom tokens, the easiest way of doing it is by
exporting (/admin/config/development/configuration/single/export)
config_token.settings.yml and copying it to your sync directory. Then you can
make the edits and commit the file.

To export the values for the custom tokens, create config_token.tokens.yml.

### Text Formats:
The following text formats are available on a Standard Drupal installation, but
you can use any format available in admin/config/content/formats.
* basic_html
* restricted_html
* full_html
* plain_text

### Displaying tokens
Up can use the tokens on fields that are processed i.e. Body

`[config_token:example_email]`

## Working with Token filter module
* Enable token filter (Replaces global and entity tokens with their values) on admin/config/content/formats
* Create a new content and put the token on the Body field i.e. [config_token:example_link]

## Working with Domain module

All you need to do to override the custom token configuration per domain is to
create a config file with this pattern:

`domain.config.[domain alias].config_token.tokens.yml`

### TODO

* Alter Token cache context to add url.site
* Implement the UI for exporting configuration in the config_token_ui submodule.
