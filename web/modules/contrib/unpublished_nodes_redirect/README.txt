
---------------------------------
UNPUBLISHED NODES REDIRECT MODULE
---------------------------------


CONTENTS OF THIS FILE
---------------------

 * About Unpublished Nodes Redirect
 * Configuration
 * API Overview


ABOUT UNPUBLISHED NODES REDIRECT
--------------------------------

http://drupal.org/project/unpublished_nodes_redirect

Unpublished Nodes Redirect is a simple module to allow admin users to setup
redirects for each node type on their site. They can also set different types
of redirects per node type. Developers can alter the node type list if required.
The redirect will only effect anonymous users, if you have admin users that do
not have permissions to view unpublished nodes, they will still see a
403 Access Denied for these pages.


CONFIGURATION
-------------

Settings page is located at:
8.x: admin/config/system/unpublished-nodes-redirect

 * Internal redirect path - If left blank then Drupal will use a standard
   403 Access Denied for nodes of this type. Only internal paths are allowed
   and if a path is selected, then the response code is then required.


API OVERVIEW
------------

Alter the node types array:
hook_unpublished_nodes_redirect_node_types_alter()
 - Alter the node types array.
