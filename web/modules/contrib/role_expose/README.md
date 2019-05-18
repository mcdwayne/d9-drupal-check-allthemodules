# Role Expose

## Introduction

Role Expose -module introduces a tab on user profile page which lists user's
roles. Site administrators may choose which roles to list on the page.
Users may be granted an option to view own roles or all users roles.

## Installation

Install as you would normally install a contributed Drupal module.

See: 
[https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules](https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules)
for further information.

## Configuration 

Configure user permissions in Administration » People » Permissions.
Suggested permissions are:
* For authenticated users: View own roles
* For user admins: View roles of all users

Customize exposed roles below Role management area in Role edit form
_Administration » People » Permissions » Roles_, for each role
separately.

Exposed roles are visible in user profile page for users with permissions with
View own roles or View roles of all users (for own or all users, respectively).

## Todo

Add support for Apply for role https://www.drupal.org/project/apply_for_role
to allow easy requests for roles user does not yet have.

## Maintainers

Current maintainer:
*  Perttu Ehn (rpsu) - https://drupal.org/u/rpsu

This project was originally empirical part of a bachelor’s thesis:
* Subject: "Automatic testing in Drupal 7 module development"
* @see http://urn.fi/URN:NBN:fi:amk-2011112916150
  (in Finnish, abstract in English)
