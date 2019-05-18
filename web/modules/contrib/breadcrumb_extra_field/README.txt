
Breadcrumb extra field allows you to print breadcrumb inside node content, just
as a field, in any position.
Especially useful when you need to place the breadcrumb between the fields.

DRUPAL 7:

Installing:

Drupal 7:
Download and place in sites/all/modules/[contrib/]breadcrumb_extra_field
You must also install the Entity module (http://www.drupal.org/project/entity).

Navigate to administer >> modules and enable Breadcrumb extra field.

Drupal 8:
Download and place in /modules/[contrib/]breadcrumb_extra_field
Navigate to administer >> modules and enable Breadcrumb extra field.

Configuration:

In order to enable the extra field into an entity, is needed to configure
the module administration form:
Drupal 7 URL to configuration form: "/admin/structure/breadcrumb-extra-field".
Drupal 8 URL to configuration form:
  "/admin/config/system/breadcrumb-extra-field".

"Administer breadcrumb extra field" permission is used to ensure the
correct access. Be sure it's correctly configurated in People >> Permissions.

After configuration just clear cache and set the extra field visibility into
the desired view mode.

IMPORTANT: Limited to these entity types:
node, user, taxonomy term, comment and bean.
If you need to implement into other entity please create a feature request
issue.
