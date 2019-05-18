CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Testing
 * Maintainers


INTRODUCTION
------------

The Configuration Replace module replaces existing configuration on module installation via using a "rewrite" folder
in the config directory. Stops with an error, if you are going to replace a config without having the original config
in your database (a difference to the Configuration Rewrite module).

This can be handy, if you like to replace existing configuration (like admin user email address) without the need to
use install or update hooks. You can just place your configuration YAML files into the config/rewrite directory in your
module folder. Afterwards the existing configuration will be replaced on module installation.

 * For a full description of the module visit:
   https://www.drupal.org/project/config_replace

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/config_replace


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.

INSTALLATION
------------

 * Install the Permissions by Term module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.

TESTING
-------

This module contains Drupal Kernel Base Tests, which are based on PHPUnit. You can find them in the tests folder.

MAINTAINERS
-----------

 * Peter Majmesku - https://www.drupal.org/u/peter-majmesku

Supporting organiztion:

 * publicplan GmbH - https://www.drupal.org/publicplan-gmbh
