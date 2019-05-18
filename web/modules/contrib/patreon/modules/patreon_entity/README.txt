-- SUMMARY --

The Patreon Entity module creates new bundled fieldable entity with its own
access permissions. The entity has no specific functionality of its own.

-- REQUIREMENTS --

The module has no dependencies. It is a sub-module of the Patreon module, but
does not require that module to be enabled for its functionality. However, it
is recommended for use with the Patreon User module, which allows users from
the Patreon site to log in to Drupal and creates new roles for those users.

-- INSTALLATION --

The module can be installed as usual; see https://drupal.org/node/895232 for
further information. It is a sub-module of the Patreon module.

-- CONFIGURATION --

The entity is configurable at /admin/content/patreon_entity. The bundles are
configured at /admin/structure/patreon_entity_type.

When new content types are enabled, new permissions will be created, which will
need to be granted to roles before users can view the content type.

-- CUSTOMIZATION --

This module does not currently offer opportunity to customise, beyond
the standard customisation of entities that Drupal offers.

-- CONTACT --

Current maintainer:

* Dale Smith (MrDaleSmith) - https://drupal.org/user/2612656