CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Config Pull Request module allows Admin users to request a pull request of
config changes on live environments. When last minute/urgent changes need to be
done on the Admin UI, the user can issue a Pull request that can be reviewed and
merged by the dev team.

Pros:

 * Speeds up the process of exporting last minute/urgent changes
 * Allows Admin users to tweak the configurations quickly and keep the changes
 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/config_pr
 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/config_pr


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.

Libraries:
- knplabs/github-api:^2.10
- php-http/guzzle6-adapter:^1.1

INSTALLATION
------------

 * Install the Config Pull Request module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.

Use composer to make sure you will have all dependencies.
`composer require drupal/config_pr:^1.0`


CONFIGURATION
-------------

1. Navigate to Administration > Extend and enable the module.
1. Navigate to the /user page and add the Authentication token. To learn how
   to create authentication tokens, visit 
   https://help.github.com/articles/creating-a-personal-access-token-for-the-command-line/
1. Navigate to the module settings page,
   /admin/config/development/configuration/pull_request/settings and add the
   repo user name and repo name. Normally these are found on the repo Url.
   i.e. https://github.com/marcelovani/captcha_keypad
   the username = marcelovani and repo name = captcha_keypad

How it works:

1. After the Admin is done with the changes, they will visit the
   Configuration Management page, select the Pull Request tab, select the
   configs that they want to keep in the Pull Request. They will confirm the
   repo url and give it a title and description, then select the submit
   button.
1. The module will check user authentication on the repo and create the pull
   request. It can also notify devs about the Pull Request.
1. The devs will review, comment, accept or reject the Pull Request.

Creating pull requests:

1. Visit the Config Manager page /admin/config/development/configuration and
   select the 'Pull Request' tab.
1. Select the configs you want to add to the pull request.
1. Fill in the pull request details and select the button.
1. After the form is submitted, you will see a message with the link to the
   pull request.
1. At the bottom of the page the user will see a list of open pull requests
   for the relevant repo.


MAINTAINERS
-----------

 * Marcelo Vani (marcelovani) - https://www.drupal.org/u/marcelovani

Supporting organization:

 * Dennis - https://www.drupal.org/dennis
