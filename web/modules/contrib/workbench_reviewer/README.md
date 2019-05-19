INTRODUCTION
------------
[Workbench Reviewer](https://github.com/patrickfweston/workbench_reviewer) is a module to allow for content editors to assign individual pieces of content to other users for review.

A new "Workflow" section is added to the right-hand set of tools when editing nodes. This module provides an entity reference field where a reviewer may be tagged. In addition, the revision log message is also displayed here to make it easy to add notes about what has been updated.

Additionally, a new "Assigned to Me" view is created. This view allows editors to see which pieces of content are assigned for them to review.

REQUIREMENTS
------------
#### 8.x-1.x
Integrates with Workbench and Workbench Moderation
* Workbench: https://www.drupal.org/project/workbench/
* Workbench Moderation: https://www.drupal.org/project/workbench_moderation/

#### 8.x-2.x
Integrates with Drupal Core's Content Moderation

INSTALLATION
------------
By default, Workbench Reviewer adds the reviewer field to any content type that has moderation enabled. If moderation is not enabled, Workbench Reviewer is not used.

To enable Workbench Reviewer support, simply ensure that any content types are under some form of moderation as provided by Content Moderation.

To use Workbench Reviewer, just create a node like you would normally do. In the list of additional options that appears to the right of the content type's fields, expand the _Workflow_ section.

Assigning the node to someone to review is optional. When you mark someone else as the reviewer in the _Reviewer_ field, the node will appear in their list of items to review through the _Assigned to Me_ tab when visiting the main content listing.

CONFIGURATION
-------------
The module has no menu or modifiable settings. There is no configuration. When enabled, the module adds a reviewer field to all content types under moderation.

MAINTAINERS
-----------
Current maintainers:
 * Patrick Weston (patrickfweston) - https://www.drupal.org/u/patrickfweston
