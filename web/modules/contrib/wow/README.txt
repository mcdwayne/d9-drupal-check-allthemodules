This module is a set of a base component and products to integrate with drupal 
core. The Blizzard Community Platform API enables communication with the RESTful
 APIs exposed through the World of Warcraft community site.

http://blizzard.github.com/api-wow-docs/#id3380293

Designed with a few goal in mind:
This module contains a low-level API and a base framework for developers who
wants to write their own products upon this library.

Localized API: Support US, EU, KR, CN and TW armory.
Data Resources are supported.
wow_http_request is build upon drupal_http_request and help communicate with
battle.net API by loading settings from the configuration. Each modules contains
a function which also honor If-Modified headers to respect API limitation.
It contains a set of products which integrates drupal modules:

Character:
Create a character entity, map them to users, fetch and store information
directly from battle.net character resources endpoint.
Own multiple characters and easily swap between them.
Support an in-game verification mechanism.
Automatically update characters via cron.
Provides a good building block for user related features.

Guild:
Create a guild entity, fetch and store information directly from battle.net
guild resources endpoint.
Import your guild data and manage your roster.
Automatically update guild, progress, and roster via cron.
Provides a good building block for guild related features.

Guild ranks:
Uses guild rank to grant permission when loading a user.
Ranks mapping with roles are fully customizable through an administrative UI.
For instance, you can give a special role to your officers such as
administrator. Also, roles are automatically revoked if a user is not found in
the roster anymore (in case he left or was kick from the guild).
Provides a good example of how to build module upon wow_guild and wow_character
module. This "end user" module works directly with drupal build in permission
system.

Items:
Language support for items through entity fields localization system. Drupal
support out of the box localized fields. The item module leverage the field API
to provides localized version of a same item.
Support of Tooltip for item rollovers. Use wowhead provider by default. You can
also provide your own representation of an item.

Realms:
Provides realm status information as Drupal Entity.
Provides an ajax auto-completion callback for Realm selection textfield.
