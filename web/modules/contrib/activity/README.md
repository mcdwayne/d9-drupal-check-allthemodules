DESCRIPTION
-----------
* Activity module keeps track of the things users do on the
site and provides a view to see the actions done.
Activity view is visible on path /activities/all. For this view, you need the
 permission to view activities. Also, there is a Rest view
(see /rest/activities/all).

DEPENDENCIES
------------
* Activity has no dependencies.


CONFIGURATION
-------------
 Only a user with a role that has permission to 
 create/configure/delete can do the first two steps.
 
 1. Create an event when the actions to happen (admin/activity/create)
 2. Configure this event (admin/activity/configure/{event name})
 3. Create/Update/Delete an entity only like node, user, comment
 4. Check if the actions is triggered in view.
