User bundle


Description
-----------
This module allows for the creation of different types of users, each with their
own independent fields, form, and display settings.

What is a "bundle"?  In Drupal we have containers for information called
entities, and they come in a variety of types (eg. Node, User, File, Comment,
etc.).  Beneath these entity types we can have sub-types, which are known as
"bundles" in the Drupal community.  Take Nodes, for example.  The Node module
refers to its bundles (or sub-types) as "Content types", and most sites will
start out with two of them:  Article and Basic page, each with their own fields,
form, and display configuration.

The User bundle module introduces bundles (sub-types) to the User entity, and
refers to them as "Account types".  If you're used to working with Content
types, Block types, Comment types, Vocabularies, or any other entity
sub-types, you'll be right at home working with Account types.


Features
--------
* Define as many Account types as you wish
  (Administration > Configuration > People > Account types)
* Independently set up fields, form, and display config for each account type
* Indicate which Account type should be used for the new user registration form
  (Administration > Configuration > People > Account settings)
* View, sort, and filter users by Account type on the site's "People" view
  (Administration > People)


Comparison with Profile module
------------------------------
Profile creates a new Profile entity type, and allows for the creation of
Profile sub-types called "Profile types".  Each Profile type can have its own
fields, form, display, status, and role assignment settings.  Users who are
assigned the same role as an enabled Profile type will see the Profile type's
fields in a sub-tab when editing their account.  Fields are not attached
directly to the user entity, but instead to the Profile entity.  The Profile
entity is then associated with the User entity by way of an entity reference
field.  Profile's data model is a bit more complex than User bundle's, as it
involves a secondary entity type, roles, and role assignment.  This complexity,
however, does allow for more features than what you'll find in User bundle.

User bundle takes advantage of Drupal core's existing entity bundle system to
allow for the creation of different Account types.  Each Account type can have
its own fields, form, and display settings.  Fields are attached directly to the
user entity.  The data model is fairly simple, and utilizes the same pattern
found across all of Drupal core's entity types that support sub-types (Nodes,
Blocks, Comments, Terms, etc.).  User bundle's Account types are completely
independent of roles.  A site built with User bundle could, if desired, utilize
a module like Field Permissions to show/hide fields based on role. Similarly, a
module like Field Group or Drupal core's own Field Layout module could be used
to arrange an Account type's fields into different tabs or regions on the page.

Note:  Just like changing a Node from one type to another, changing a user from
one Account type to another is not currently possible through the administrative
interface, nor is it the goal of this module to provide such functionality.  It
is, however, programmatically possible to switch any entity's bundle, and a
separate module, Bundle switcher, already exists in this space.

Let the requirements of your particular project guide your decision.


Bugs, Features, & Patches
-------------------------
If you wish to report bugs, add feature requests, or submit patches, you can do
so on the project page on Drupal.org.
https://www.drupal.org/project/user_bundle


Authors
-------

8.x-1.x Author
--------------
Christopher Caldwell (https://www.drupal.org/u/chrisolof) <chrisolof@gmail.com>

7.x-1.x Author
--------------
Andrei Mateescu (https://www.drupal.org/u/amateescu)
