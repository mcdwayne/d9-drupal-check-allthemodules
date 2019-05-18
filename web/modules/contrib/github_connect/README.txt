CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

GitHub Connect module gives site visitors the option to register/login using
their GitHub (http://www.github.com) account.

INSTALLATION
--------------------------

Install the module like any other Drupal module.
To make use of it you will need to create a GitHub application at
https://github.com/account/applications/new.
It is important to set the correct URLs here or else the module won´t work.
Main URL: http://<yourdomain.com>/
Callback URL: http://<yourdomain.com>/github/register/create

CONFIGURATION
--------------------------

* Enter the Client ID and Client Secret from you GitHub application at the
modules settings page (/admin/config/people/github_connect).
* Place the GitHub Connect block where you want it to be displayed.


Maintainers
---------------------

vikom (Viktor Miranda)
nehajyoti (Jyoti Bohra)
