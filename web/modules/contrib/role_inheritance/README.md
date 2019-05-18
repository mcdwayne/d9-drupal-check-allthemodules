# Role Inheritance

Added Role Inheritance to Drupal 8

## About

This module allows role inheritance for complex organizations. This reduces the
number of duplicated or assigned privs and access.

## Example

For a new site, there are *Writers, Editors and Global Editors*. The Writers and
editors are assigned privs to write/post/edit content on seperate sections
(Sports, Entertianment, Weather, etc...) and the *Global Editor* has access
accross all sections.

Role inheritance could be setup such that *Global Editors* inherit privs from
*Editors* and *Editors* Inheirt Privs from *Writers*. Thus the create content
priv only needs to be assigned to *Writers* and the other two roles will inherit
all their access.

When incorperated with Workbench Access or Taxaonomy Access Control, Roles can
inherit thses accesses privs as well to define access over sections and content.
