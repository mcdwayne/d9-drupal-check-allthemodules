/**
 * urvp: Ubercart Extras - Recently Viewed Products Block
 *
 * Original credits, 1.x development:
 *
 * Developed by Joshua Fernandes
 */

INTRODUCTION

1) This module provides users to see theirs recently viewed ubercart products.

2) For anonymous users recently viewed products
listing will get cleared once browsers session expires.

REQUIREMENTS

1) Ubercart Module is required.

INSTALLATION

1) Download the latest release of this module .

2) Uncompress the archive in your Ubercart contrib directory:
[your Drupal root]/modules/contrib.

3) Enable the Ubercart Recently Viewed Products module under
'Ubercart - extra' in the Drupal module administration page.


CONFIGURATION

1) Module configuration can be done from
/admin/store/config/uvrp.

2) Go to admin/structure/block.

3) Assign Ubercart Recently Viewed Products block to theme region.

FEATURES

1)This module provides users to see theirs recently viewed ubercart products.
2) For anonymous users recently viewed products listing
will get cleared once browsers session expires.


DEVELOPMENT

The module introduces one new table:

{uvrp}
nid (int) -- product node id
uid (int) -- user id
sid (int) -- session id
ip (text) -- users ip address
created (int) -- timestamp when product is viewed
