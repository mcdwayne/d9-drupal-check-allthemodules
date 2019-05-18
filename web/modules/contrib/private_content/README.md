This module provides a very simple access control based around the concept of a private node.
Any node marked as private can only be accessed by authorised users, based on two new permissions:
* Access private content
* Edit private content

This module affects access only for nodes marked as private, denying access unless the user has the matching permission.
The private flag has no affect on the author of a node.  This module *never* adds access to a node that wouldn't otherwise be available.

# WARNING
This module uses an access control mechanism in Drupal called "node grants".  By default, Drupal disables node grants checking.
If you enable this module, or any other based on "node grants", Drupal enables grants checking, and this affects
your website:
* There may be a decrease in performance as Drupal runs extra checks.
* There are subtle changes to the access granted in certain scenarios for unpublished nodes.
  For details, see https://www.drupal.org/project/unpublished_access.
