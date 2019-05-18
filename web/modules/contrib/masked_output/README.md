# Masked Output

## Description

There is always some data which needs to be masked for some fields as output
This module deals with masking of such fields when shown in Entity View Mode

Eg:
* Credit card number : **********2016
* SSN : *****1468
* Phone : 765345####

Module offers a Field Manage Display option to customize the masking output.

Module works with Field type
1) Text(Plain)
2) Email

## Requirements

* [Masked Output](https://www.drupal.org/project/masked_output)

## Installation

### Using Composer
* Composer require drupal/masked_output
* drush -y en masked_output

### From admin panel
* Download the module from https://www.drupal.org/project/masked_output
* Unzip and place in  path '/modules/contrib/'
* Navigate to /admin/modules and search for 'Masked Output'
* Check and click Install button

## Configuration steps
1. Select 'Manage display' tab for the Content type.
2. Select 'Mask Output' option from the selection box under Format column for
   which you wish to mask the output.
3. A default masking is set (click 'Save' if you wish to go with the default
   setup).
4. Click the settings icon to change the masking setup.
5. Change the setting as per the requirement and click 'Update'.
6. Click 'Save' to save the settings.

## Maintainers

* Girish G [(ggh)](http://drupal.org/u/ggh)
* Ram Reddy Kancherla [(ramreddykancherla)](https://www.drupal.org/u/ramreddykancherla)
