INTRODUCTION
------------

Views Access provides more granular permissions on views based on tags. This is
really helpful if certain users should be allowed to edit certain views but not
other. As an example if views is being used to build reports but report builders
shouldn't be able to change site views. Permissions are created for each
operation and each tag that a view has.

Administrators can choose which tags provide permissions, including adding tags
that aren't currently in use so sites can be configured in advance of a view
being created.

REQUIREMENTS
------------

This module requires the following modules:

  - Views UI (core)

INSTALLATION
------------

  - Install as you would normally install a contributed Drupal module.

CONFIGURATION
-------------

  - Configure which tags should provide permissions in Administration » Views »
    Settings » Tag access.

  - Configure user permissions in Administration » People » Permissions. The
    'Administer views' will allow access to all views, so not be given to a user
    for the following permissions to have effect:

    - Create views
      This permission allows the user to create views*.

    - Administer views with the TAG tag
      This permission allows the user full access to all views with the the tag
      TAG.

    - Delete views with the TAG tag
      This permission allows the user to delete any view with the the tag TAG.

    - Disable views with the TAG tag
      This permission allows the user to disable any view with the the tag TAG.

    - Duplicate views with the TAG tag
      This permission allows the user to duplicate any view with the the tag
      TAG*.

    - Enable views with the TAG tag
      This permission allows the user to enable any view with the the tag TAG.

    - Update views with the TAG tag
      This permission allows the user to edit any view with the the tag TAG.

  * Note: Users who create or duplicate a view will have full access to that
  view for the rest of their session. After that, it will revert to tag based
  permission.
