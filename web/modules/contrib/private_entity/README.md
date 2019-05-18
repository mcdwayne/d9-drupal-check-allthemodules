Simple entity access control set on the entity create or edit form,
limited to the view operation.

## Configuration

- Add a 'Private entity' field for the desired content types.
- Set the 'View private entities' permission for the desired roles.
- Optionally edit the system wide configuration to display/hide the content 
access status message that is shown after having saved an entity.

The first alpha releases will be limited to the Node entity type.
There is a work in progress to cover other entity types.

## Use case

Uses node access records, so it can be used with Search API indexes,
Views, ... with no other configuration than the field and the permission.
 
It is suitable when you want:

- A single privacy rule for viewing content that can be applied per role.
- A way to set the content access straight from the entity create / edit form.

It should not be used with other modules that are covering
content access control.

## Related modules

- [Content Access](https://www.drupal.org/project/content_access)
- [Group](https://www.drupal.org/project/group)

## Roadmap

- Unit tests
- Redirect 403 to user login then redirect after login to the entity 
that tried to be accessed.
- Cover other entity types.
- Bulk edit from the /admin/content list.
- Use a custom publishing option as an alternative to a field.
