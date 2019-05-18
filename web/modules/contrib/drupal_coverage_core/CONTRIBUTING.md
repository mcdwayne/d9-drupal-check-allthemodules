Drupal Coverage For Developers: Contributor's Guide
===================================================

Drupal Coverage is a community project.

If you'd like to participate in Drupal Coverage development, thank you!

Today we are using a development environment on Acquia in order to test this
project. If you would like to start contributing on this project, feel free to
request access to the docroot via the issue queue.
In order to understand how this project works, have a look at legovaer's session
at DrupalCamp Ghent 2016.


Policies
--------

Drupal Coverage follows the Drupal core process as much as possible.

Contributions thus need to be similar in quality to Drupal core patches.
Contributions will need to meet the following minimum standards:

### Normal Drupal issue process

Drupal projects use patches related to issues. You should know how to make a
patch and an interdiff using git. It's fine to develop on GitHub or
what-have-you, but eventually it has to be a patch that can be reviewed in the
normal Drupal issue process. See the list of resources for some information on
how to do this.

Your patch will also need to be reviewed by someone other than yourself. Learn
about the review process in the resources section.

### DrupalCI

Examples uses the Drupal automated testing system to verify the applicability of
patches. See `TESTING.md` for details.

### Drupal coding standards

All code in Examples should adhere to the Drupal core coding standards. Examples
uses the Drupal Coder project and PHP_CodeSniffer to enforce coding standards.
Think of this as another test your code must pass. See `STANDARDS.md` for
details.


Resources
---------

### Novice

Drupal novice contribution guide: https://www.drupal.org/novice

Drupal contribution guide: https://www.drupal.org/contribute

What's a patch? https://www.drupal.org/patch

How to make a patch with git: https://www.drupal.org/node/707484

### Everyone

How to review a patch: https://www.drupal.org/patch/review

See `STANDARDS.md` and `TESTING.md` for information on how to run a coding
standards test, and also how to run the tests themselves.
