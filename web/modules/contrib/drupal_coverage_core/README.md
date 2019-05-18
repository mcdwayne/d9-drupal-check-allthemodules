Drupal Coverage
===============

CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Maintainers

Introduction
------------

Drupal Coverage is an online service that provides on-demand code coverage
reports for both contributed and core modules.

Project site: http://drupal.org/project/drupal_coverage_core

Code: https://drupal.org/project/drupal_coverage_core/git-instructions

Issues: https://drupal.org/project/issues/drupal_coverage_core


Architecture
------------

A new coverage report for a given project can be initiated from within the user
interface of Drupal Coverage. Based upon the project which was selected, Drupal
Coverage will inject environment variables into Travis CI via the REST API.
Travis CI will fetch the .travis.yml file from a GitHub repository and will
replace the hard-coded variables with the environment variables.

The first step of the build process is installing Xdebug, phpcov-runner & Drupal
TI. Drupal TI will allow Travis CI to install a fresh Drupal installation. For
Drupal Coverage, we have added several other functionalities to Drupal TI to
make sure that it is capable of creating a Code Coverage report for SimpleTest.

DrupalTI will perform several tasks in order to generate the code coverage
report:

Start PHPCOV-Runner so that all of the following executed code will be recorded.
Execute Drupal's run-tests.sh and specify the test group which needs to be
executed.
PHPCOV-Runner keeps storing all of the data of executed lines inside a local
SQLite database.
When the execution of run-tests.sh is completed, DrupalTI will execute the stop
command on PHPCOV-Runner. Now all of the data from the SQLite database gets
retrieved and a Code Coverage report is being generated.
A custom script will generate a Code Coverage badge based upon the results of
the report.
The final step of Drupal TI will add both the generated coverage report and the
created badge to the same GitHub repository in a unique identified branch.
In the background, Drupal Coverage keeps checking with TravisCI if the build
has been completed. When the build has been completed, the user will be able to
see the results in his browser.

This is made possible by using RawGit which hosts .html files from within
GitHub.

You can also have a look at our infographic (see project page) about the
architecture of Drupal Coverage.


Installation
------------

Normally you shouldn't use this module in your website. We have decided to share
this module with the community in order to help people to understand how this
project actually works.

You can use http://drupalcoverage.org instead.

If you find a problem, incorrect comment, obsolete or improper code or such,
please search for an issue about it at
http://drupal.org/project/issues/drupal_coverage_core If there isn't already an
issue for it, please create a new one.

Thanks.


Maintainers
-----------

Current maintainers:
 * Levi Govaerts (sun) - https://drupal.org/u/legovaer

This project has been sponsored by:
 * Capgemini
   Capgemini is one of the world's foremost providers of consulting, technology,
   and outsourcing services with around 180,000 employees and presence in 40
   countries.
   We have around 300 Drupal developers worldwide, making us one of the biggest
   Drupal integrators globally.
