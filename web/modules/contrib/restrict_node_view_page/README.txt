CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation

INTRODUCTION
------------

Current maintainer:  Kurinjiselvan V (https://www.drupal.org/u/kurinji-selvan)

Restrict node view page access modules helps to control the view the node full
view page. We can restrict the node full view page by specific the user roles. 

After enabling the module, we need to give the permission to the user roles
who will view the page by content type based.

This module having the same functionality of Restrict node page view (https://www.drupal.org/project/restrict_node_page_view) which is only 
available in Drupal 7 which is developed by Christian Johansson (freakalis) https://www.drupal.org/u/freakalis.

This module having two permissions
  1. View full node pages of all content types :
     - It's give permission to view all full node pages in all content types.
  2. View full node pages of 'specific content type' : 
     - It's give permission to view full node pages in 'specific content type'.

INSTALLATION
------------

* Install as usual, see https://www.drupal.org/node/1897420 for further information.

* After the successfull installation please set the permissions to the user's
(<root>/admin/people/permissions), find "Restrict node view page access".
