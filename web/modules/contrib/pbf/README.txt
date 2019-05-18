CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Similar modules
 * Installation
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers

INTRODUCTION
------------

Permissions by field provide a new Field Type which enhance the Entity
Reference field type.

By adding fields of type Permissions by fields, you can manage access to the
node hosting these field by referencing other entity and specifiy which access
you want to grant (none or standard permission, view, update, delete) for users
which reference same entities (node, term, role or other users). You can also
grant access to the node directly per user if needed.

You can reference with the field type Permissions by field these entity types :
- user
- node
- term
- role

REQUIREMENTS
------------

This module provide a field type bases on the Entity Reference field. Entity
Reference is provided by Drupal core.


SIMILAR MODULES
-------------------

There is a lot modules which handle access permissions for node :

- Content access manage access per node type or per node and grant access to
  roles
- Permissions by terms handle permissions for nodes and users which reference
  same taxonomy term with a determined field.
- Organic Group which permit to manage group and related permissions inside
  each group

The goal of this module is to provide a simple method for implementing
similar but lighter features than thoses provided by Organic Group, currently
being ported to Drupal 8.

By using a specific field enhancing entity Reference field, you can easily
manage access to node in multiples way.

- For users members of a group node (who reference a content type named Group
  for example)
- Per user individually
- By role assigned to users
- By Term referenced by the node and users
- for users which reference others users

See documentation for more explanation about possibles configurations.

INSTALLATION
------------
Install as you would normally install a contributed Drupal module. See:
https://www.drupal.org/documentation/install/modules-themes/modules-8
for further information.


CONFIGURATION
-------------

- Add a field Permissions by field to the content type you want manage access.
- Choose the entity type this field will reference
- In the field's settings form, you can set default values and configure target
  bundles, as any Entity Reference field. You can set too the default access
  provided by this field.
  For example, we can add on the content type article a field pbf_ref_group
  which reference the content type Group.
- Add a field Permissions by field to user entity type. This field *must* have
  the same machine name (pbf_ref_node) as the field added to the content type,
   and configure it to reference too the content type Group.
- It's done. Now users who reference Group will grant access to the the content
  Article which reference same Group with the permissions you set on the field
  Permissions by Field.

Note that the checkbox Public in the grant access options, if checked, permit
you to use standard permissions of the Drupal site. You can then add a content
type to a Group but let this content be accessible to all the users who have
standard permission "access content".

Node's author will always granted all permissions.

TROUBLESHOOTING
---------------


FAQ
---


MAINTAINERS
-----------

Current maintainers:
 * flocondetoile - https://drupal.org/u/flocondetoile
