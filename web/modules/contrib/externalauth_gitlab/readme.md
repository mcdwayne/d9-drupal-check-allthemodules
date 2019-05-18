# Externalauth Gitlab OAuth2 connector

Externalauth Gitlab OAuth2 connector will allow users of your site to authenticate against a running gitlab instance via OAuth2. The module will not register new users, just map existing users via the email-address.

## Dependencies:

* [externalauth](https://www.drupal.org/project/externalauth)


## Installation

* Install via composer `composer require drupal/externalauth_gitlab`
* this should install all needed depencencies
* Enable the module
* Configure the module at `/admin/config/people/externalauth-gitlab-settings`

## Usage

* The module will add a new local task at `/user`
* When visiting that link the user will gets redirected to the gitlab instance, if the user authenticates successfully, it will get authenticated in Drupal.

