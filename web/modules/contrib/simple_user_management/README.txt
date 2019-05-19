
Simple User Management Module Readme
--------------------------------------

This module provides a way to let clients easily approve users as well as grant
them particular roles such as site editors assigning editor role to other new
users (but avoiding editors granting or gaining administrator access). The
module provides quick access operations from the people list to approve and
delegate roles.

It uses the excellent Role Delegation module under the hood for delegating roles
and provides it's own simple interface for approving users without the need
for the 'administer users' permission.

Example use cases
-----------------

You want to provide a very simple interface to let your client manage site
editors and authors but want to avoid your clients gaining administrator access.
You may have opened up registration with approval and want your client to be
able to approve users (eg, a new colleague) and grant them a particular role.

Installation
------------

To install this module:

* place it in your modules folder and enable it on the
modules page.
* grant the desired role (such as 'editor') 'view user information' permission
  via admin > people > permissions.
* grant the desired role (such as 'editor') the desired role delegation
  permissions such as delegating the 'editor' and 'author' role, avoiding
  allowing delegation of the administrator role to keep your site safe.
* go to admin > structure > views and set the permission of the view to be
  'view user information' and save the view.
* optionally add a quicklink or add the admin > people to a menu location that
  your non-admin role (such as 'editor') can access

Feedback on this module
-----------------------

Please add issues with feature requests as well as feedback on the existing
functionality.

Supporting organizations
------------------------
Development and maintenance of this module is sponsored by Fat Beehive
