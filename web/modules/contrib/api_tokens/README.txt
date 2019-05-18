-- SUMMARY --

The API Tokens module provides an input filter that allows to replace custom
parametric tokens (API tokens) with rendered content. To register an API token
define an "ApiToken" plugin.

For example, you can define a token which will render a current date as follows:
  [api:date/],
or render blocks:
  [api:block["block_id"]/],
or nodes:
  [api:node[123, "teaser"]/].

Note that API Tokens module does not provide any visible functions to the user
on its own, it just provides handling services for other modules. See
api_tokens_example module for example implementations.

To disable caching of dynamic API tokens for anonymous users disable "Internal
Page Cache" (page_cache) core module.


For a full description of the module, visit the project page:
  http://drupal.org/project/api_tokens

To submit bug reports and feature suggestions, or to track changes:
  http://drupal.org/project/issues/api_tokens


-- REQUIREMENTS --

Filter.


-- INSTALLATION --

* Install as usual, see http://drupal.org/docs/8/extending-drupal-8 for
  further information.


-- CONFIGURATION --

* Go to /admin/config/content/formats and enable the API Tokens filter for any
  of your existing text formats or create a new one.

* List of all registered tokens available at admin/config/content/api-tokens.


-- CONTACT --

Current maintainers:
* Alex Zhulin - https://drupal.org/user/2659881
