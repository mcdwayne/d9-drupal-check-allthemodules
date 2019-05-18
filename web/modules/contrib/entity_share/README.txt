CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Similar modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

This module allows to share entities using the JSON API. It provides an UI to
use the endpoints provided by the JSON API module.

You can define one website as a "server" and another website as a "client". A
website can be both "client" and "server".

Currently you can only, on the client website, get content exposed from the
server website. You can't push content from the client website to the server
website.

When pulling content, referenced entities are also pulled recursively. If you
reselect a content it will be updated and referenced entities will also be
updated recursively.

The entities you want to share must have the same structure (machine name, field
machine name and storage) across the different websites.

Note about links and embed entities in RTE:

To ensure the share of links referencing entities (most of the time content) and
entities that are embedded in RTE, we recommend to use the following modules, as
they use UUID to operate:
 * Linkit (https://www.drupal.org/project/linkit)
 * Entity Embed (https://www.drupal.org/project/entity_embed)

This module does nothing to ensure the embed entities are shared automatically,
you must share the entities by yourself.

Note about multilingual content:

When pulling translatable content, the default langcode is dropped to avoid to
have to pull the content in its original language and because the original
language may be not enabled on the client website.

Referenced entities will be imported in the same language as the referencing
entity if possible. If a referenced entity is not available in the same
language, Drupal will display the entity in the first language available
depending of the languages weight.

Note about CRON usage:

If you want to synchronize entities automatically using CRON, there is a test
module 'entity_share_client_test' that provides example code.

Note about Entity share client module:

As the Entity share client sub-module has a dependency on the JSON API module,
on your client website, content and configuration will be exposed in JSON API
endpoints, by default on /jsonapi. As the JSON API use the Drupal access API to
check access to entities, if you have used the access API and permission system
correctly, users will not have access to content they should not access. But for
example, they will be able to access fields not displayed in view modes.

So to add a new security layer, it is advised to block requests on JSON API
endpoints on your client website (and also if needed or possible on your server
website). This configuration can be done in your webserver configuration to
block external requests and only authorized requests coming from internal
networks or trusted IPs.

This configuration will differ based on the webserver you are using (Apache,
Nginx, Microsoft IIS, etc.) and also based on your network structure, for
example if you have a cache server (Varnish or other) or load balancer (Nginx,
HAProxy, etc.).

Limitation:

Currently we do not handle config entities and user entities to avoid side
effects.


REQUIREMENTS
------------

This module requires the following modules:
 * JSON API (https://www.drupal.org/project/jsonapi)


RECOMMENDED MODULES
-------------------

 * JSON API Extras (https://www.drupal.org/project/jsonapi_extras):
   To allow to customize the JSON API endpoints. See the troubleshooting section
   about the link fields.


SIMILAR MODULES
---------------

 * Entity pilot (https://www.drupal.org/project/entity_pilot): Entity share does
   not require any subscription to a service.


INSTALLATION
------------

 * Install and enable the Entity share server on the site you want to get
   content from.
 * Install and enable the Entity share client on the site you want to put
   content on.


CONFIGURATION
-------------

On the server website:
 * Enable the Entity share server module.
 * Optional: Prepare an user with the permission "Access channels list" if you
   do not want to use the admin user.
 * Go to the configuration page, Configuration > Web services > Entity share >
   Channels (admin/config/services/entity_share/channel) and add at least one
   channel.

On the client website:
 * Enable the Entity share client module.
 * Go to the configuration page, Configuration > Web services > Entity share >
   Remote websites (admin/config/services/entity_share/remote) and create a
   remote website corresponding to your server website with the user name and
   password configured on the server website.
 * Go to the pull form, Content > Entity share > Pull entities
   (admin/content/entity_share/pull), and select your remote website, the
   available channels will be listed and when selecting a channel, the entities
   exposed on this channel will be available to synchronize.


TROUBLESHOOTING
---------------

 * Taxonomy hierarchy: to handle taxonomy hierarchy, a patch on Drupal core is
   required: https://www.drupal.org/node/2543726#comment-12254548
 * Internal link fields: As Drupal stores the id of entities for internal link
   fields that reference entities, we need Drupal to store the value of these
   fields using UUID. There is an issue for that
   https://www.drupal.org/node/2873068.
   As a workaround, it is possible to use the JSON API Extras module to alter
   the data for link fields. for the concerned JSON API endpoints, you can use
   the field enhancer "UUID for link (link field only)" on the link fields.
   Note 1: This configuration must be applied and identical on both websites
   (server and client).
   Note 2: If the target entity of a link field value has not been imported yet,
   the value of the link field will be unset. So an update will be required to
   update the link field value.


MAINTAINERS
-----------

Current maintainers:
 * Thomas Bernard (ithom) - https://www.drupal.org/user/3175403
 * Florent Torregrosa (Grimreaper) - https://www.drupal.org/user/2388214

This project has been sponsored by:
 * Smile - http://www.smile.fr
