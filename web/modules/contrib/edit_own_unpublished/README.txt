This module will grant edit access for one's own node to an owner that has the
"[nodetype] node: Edit own unpublished content" permission only when that node
is not published.

The intended use is for the user to also *lose* edit access after the node
has been published.

However, this module only grants access, it does not *revoke* access. Therefore,
if a user has any additional permissions, such as "Administer Content,"
"Bypass Node Access," "Edit Own [Nodetype] Content" then they will not lose
access to edit after the node has been published and this module will not
work as expected.

Additionally, if a user has permission to publish nodes, and the user does not
 have any of the standard permissions that would grant edit to the node
 but *does* have this module's permissions, the "Save and Publish" button will
 now read "Save, publish, and lose edit access."

The ability to publish or unpublish a node is associated with the
"Administer Content" permission. Assigning that permission would be
incompatible with this module's permissions framework. The Override Node Options
module [https://www.drupal.org/project/override_node_options] gives you the
ability to assign the ability to publish nodes to roles independent of the
"Administer Content" permission.
