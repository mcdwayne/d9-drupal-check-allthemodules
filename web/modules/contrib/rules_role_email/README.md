# Rules Role Email for Drupal 8

## Introduction

The Rules Role Email module provides an action to the rules module that sends
emails to specified roles that an admin chooses.

* Project homepage: https://www.drupal.org/project/rules_role_email

## Requirements

This module requires the following modules:

* Rules (https://drupal.org/project/rules)
* Typed Data (https://drupal.org/project/typed_data)

## Installation

* Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.

## Configuration

### Configure the reaction rule in Administration » Workflow » Rules

1. Click "Add reaction rule".
2. Fill in the information about your reaction rule and
   click Save.
3. Click "Add action".
4. In the "Action" dropdown, under the User category,
   select "Send an email to all users of a role" and click
   the Continue button.
5. Under roles, enter the machine name for each role.
   For example, the "Authenticated User" role would be
   "authenticated".
   Each role is on it's own line.
6. Enter in the Subject and Message fields.
7. For the "Node" field, you can optionally enter in "node"
   to support tokens but make sure you click
   "Switch to data selection" first. See: https://www.drupal.org/node/390482
   for more information about tokens in Drupal.
8. Click Save and remember to also save your rule.
