## Contents of this file

* Introduction
* Requirements
* Recommended modules
* Installation
* Configuration
* Maintainer


## Introduction

The “Fix Teaserlinks” module is a simple module that may be used to
manage the visibility of the links that appear below teasers, i.e.:

* “Add new comment”, “Log in [or register] to post comments”

* “*X* comments” (where *X* is the number of comments)

* “*X* new comments” (where *X* is the number of new comments)

* “Read more”

It is intended for site builders who want a cleaner look of teaser
lists.

* For a full description of the module, visit the [project page][1].

* To submit bug reports and feature suggestions, or to track changes
  visit the project's [issue tracker][2].


## Requirements

None.

## Recommended modules

* [Advanced help][4]:  
  When this module is enabled, display of the project's `README.md`
  will be rendered when you visit
  `help/fixteaserlinks/README.md`.

# Installation

1. Install as you would normally install a contributed drupal
   module. See: [Installing modules][6] (D7) or [Installing
   contributed modules][7] (D8) for further information.

2. Enable the “Fix Teaserlinks” module on the Modules list page in the
   administrative interface.

3. Clear or rebuild all caches.

    drush cr

## Configuration

The module has no menu. By default, it will not do anything until you
change a setting from its default `false` to `true`.

Use the following *drush* commands to remove the links from the
teaser:

    drush config-set fixteaserlinks.settings fixteaserlinks_comment 1; drush cr
    drush config-set fixteaserlinks.settings fixteaserlinks_comcount 1; drush cr
    drush config-set fixteaserlinks.settings fixteaserlinks_newcount 1; drush cr
    drush config-set fixteaserlinks.settings fixteaserlinks_readmore 1; drush cr

The first of those drush commands will stop removing comment links.
The second will remove the comment count.  The third will remove the
new comment count.  The last will remove the “Read more” link.

To restore the link, set the variable `false` (D7) or equal to `0`
(D8).

You have to clear or refresh all caches to see the results of any
change to the configuration.

To cancel all these effects and delete the variables, unistall the
module and clear or refresh all caches.


## Maintainer

* [gisle](https://www.drupal.org/u/gisle)

[1]: https://drupal.org/project/fixteaserlinks
[2]: https://drupal.org/project/issues/fixteaserlinks
[3]: https://www.drupal.org/project/advanced_help_hint
[4]: https://www.drupal.org/project/advanced_help
[5]: https://www.drupal.org/project/markdown
[6]: https://www.drupal.org/node/895232
[7]: https://www.drupal.org/node/1897420
