# CONTENTS OF THIS FILE

* Introduction
* Requirements
* Installation
* Configuration
* Maintainers

## INTRODUCTION

This module builds upon the functionality provided by 
[Permissions by Term](https://www.drupal.org/project/permissions_by_term) 
in the following ways:

* registers a subscriber for the event fired by PbT in case of Access Denied
* sends a redirect to the login form if:
  * the user is anonymous
  * they are trying to directly access the restricted node
* after a successful login sends a redirect back to the originally requested 
node

## REQUIREMENTS

In order to work as expected this module requires the following module to be
 enabled:

* [Permissions by Term](https://www.drupal.org/project/permissions_by_term)

## INSTALLATION

 * Install the Permissions by Term Redirect module as you would normally 
   install a contributed Drupal module. 
   Visit https://www.drupal.org/node/1897420 for further information.

## CONFIGURATION

Nothing to configure here. Once enabled the module should run as described 
above.

## MAINTAINERS

 * Marc-Oliver Teschke - https://www.drupal.org/u/marcoliver

## Supporting organization:

 * publicplan GmbH - https://www.drupal.org/publicplan-gmbh
