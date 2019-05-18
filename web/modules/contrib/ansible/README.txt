CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Ansible module runs Ansible playbooks with Drupal.

The Ansible module includes 3 modules:

 * Ansible: Ansible API
 * Webform - Ansible Integration: Create custom form with Webform handler and
   send all variable in Ansible extra-vars
 * Ansible form example: Example form (requires webform module)

 * For a full description of the module visit:
   https://www.drupal.org/project/ansible

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/ansible


REQUIREMENTS
------------

This module requires the following:

 * asm/php-ansible library - https://packagist.org/packages/asm/php-ansible


INSTALLATION
------------

 * Install the Ansible module as you would normally install a contributed Drupal
   module. Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Configuration > System > Ansible
       configuration.
    3. Create a form using Webform and add the Ansibile handler.
    4. Execute the form.


MAINTAINERS
-----------

 * Ines WALLON (liber_t) - https://www.drupal.org/u/liber_t
