/**
 * Unique Field module for Drupal (unique_field)
 * Compatible with Drupal 8.x
 *
 * By Immanuel Paul, Logesh waran(https://www.drupal.org/u/logesh-waran) and
   Sidheeswar(https://www.drupal.org/u/sidhees)
 */

CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

The Unique Field module provides a way to require that specified fields or
characteristics of a node/taxonomy term/user are unique. This includes the
title, language, taxonomy terms, and other fields.

Without this module, Drupal and CCK do not prevent multiple nodes from
having the same title or the same value in a given field.

For example, if you have a content type with a Title field and there
should only be one node per Title, you could use this module to prevent a
node from being saved with a Title already used in another node. As an
imporvement we have implemented unique field for user and taxonomy fields.

INSTALLATION
------------

Install as you would normally install a contributed Drupal module.
For help regarding installation, visit:
https://www.drupal.org/documentation/install/modules-themes/modules-8

CONFIGURATION
-------------

-conent type 

This module adds additional options to the administration page for each
content type (i.e. admin/structure/types/manage/<content type>) for
specifying which fields must be unique. The administrator may specify
whether each field must be unique or whether the fields in combination
must be unique.
Also, the administrator can choose whether the fields must be unique among all
other nodes or only among nodes from the given node's content type.

Alternatively, you can select the 'single node' scope, which allows you
to require that the specified fields are each unique on that node. For
example, if a node has multiple, separate user reference fields, this
setting will require that no user is selected more than once on one node.

-Taxonomy fields

This module adds additional options to the administration page for each
taxonomy vocabulary (i.e. admin/structure/taxonomy/manage/<taxonomy vocabulary>)
for specifying which fields must be unique.
As like content type the administrator may specify whether each field must
be unique or whether the fields in combination must be unique and
control other options of it, same as above.

-User Fields

This module adds additional options to the account settings
page (i.e. admin/config/people/accounts).
The administrator can control the unique field settings for
user account fields (i.e. fields added in admin/config/people/accounts/fields).

MAINTAINERS
------------

Current Maintainers for Drupal 8 version:
*Immanuel Paul (immanuel.paul) - https://www.drupal.org/u/immanuelpaul
*Logesh (Logesh waran) - https://www.drupal.org/u/logesh-waran
*Sidheeswar Sekaran (sidhees) - https://www.drupal.org/u/sidhees
