# Username Enumeration Prevention

| Branch | Build Status |
| ------ | ------------ |
| [8.x-1.x](https://www.drupal.org/project/username_enumeration_prevention/releases/8.x-1.x-dev) | [![CircleCI](https://img.shields.io/circleci/project/github/nicksantamaria/drupal-username_enumeration_prevention/8.x-1.x.svg?style=for-the-badge)](https://circleci.com/gh/nicksantamaria/drupal-username_enumeration_prevention/tree/8.x-1.x) |
| [7.x-1.x](https://www.drupal.org/project/username_enumeration_prevention/releases/7.x-1.x-dev) | [![CircleCI](https://img.shields.io/circleci/project/github/nicksantamaria/drupal-username_enumeration_prevention/7.x-1.x.svg?style=for-the-badge)](https://circleci.com/gh/nicksantamaria/drupal-username_enumeration_prevention/tree/7.x-1.x) |

Username Enumeration Prevention is a project which aims to mitigate common ways that anonymous users identifying valid usernames on a Drupal site.

## What Is Username Enumeration?

Username enumeration is a technique used by malicious actors to identify valid usernames on a web application, which can then be used in other attacks such as credential stuffing.

## What does Username Enumeration Prevention do?

* Provides warnings on admin status report if site is configuration could expose usernames (7.x, 8.x)
* Prevents password reset form from displaying the following messages (7.x, 8.x)
  * `'%name is blocked or has not been activated yet.'`
  * `'%name is not recognized as a username or an email address.'`
* Converts 403 Access Denied responses to 404 Not Found on user profiles. (7.x)

## Additional Notes

Enabling this module is one step to preventing the usernames on the system from being found out but there are other known methods that are just as easy.

* If a user belongs to a role that has "access user profiles" granted to it, then that user can serially visit all integers at the URL http://drupal.org/user/UID and get the username from the loaded profile pages.
* "submitted by" information on nodes or comments, views, exposed filters or by other contributed modules can also expose usernames. Site builders looking to hide usernames from comments and nodes should look at using realname or some other tool.
* Browser autocompletion on the user login page can be disabled using the [Security Kit](https://www.drupal.org/project/seckit) module.

### Core Issue

Anyone looking to contribute to this project should first review the [core issue](https://www.drupal.org/project/drupal/issues/1521996) and see if there is any way they can help push that forward.

## Get Started

### Composer

* Add the project to your project's composer dependencies.
    ```sh
    composer require "drupal/username_enumeration_prevention"
    ```
* Navigate to **Administer >> Extend**.
* Enable Username Enumeration Prevention.

### Manual

* Place the entirety of the module directory in
modules/contrib/username_enumeration_prevention.
* Navigate to **Administer >> Extend**.
* Enable Username Enumeration Prevention.

## Contribute

Development of this module takes place on [GitHub](https://github.com/nicksantamaria/drupal-username_enumeration_prevention).

* If you encounter issues, please [search the backlog](https://github.com/nicksantamaria/drupal-username_enumeration_prevention/issues).
* Please [create issues](https://github.com/nicksantamaria/drupal-username_enumeration_prevention/issues/new?labels=bug) and [feature requests](https://github.com/nicksantamaria/drupal-username_enumeration_prevention/issues/new?labels=enhancement) in GitHub.
* Even better, feel free to fork this repo and [make pull requests](https://github.com/nicksantamaria/drupal-username_enumeration_prevention/compare).
