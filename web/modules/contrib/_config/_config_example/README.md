
Contents of this file
---------------------

 * About this module
 * Examples
 * Notes

About this Module
-----------------

Provides examples for using the `_config.module` to add help text, alter forms, 
and add descriptions to roles.


Examples
--------

### Add help text

Problem

- Help text can be difficult to maintain using hook_help().

Solution

- Move hook_help() text to a custom configuration file.

Examples

- Add content (node/add)
- Create Basic page (node/add/page)

Notes

- Most websites don't need to have translatable hook_help() text. So storing 
  plain non-translatable string in an `_config` directory is fine.

### Adds descriptions to role on user edit form

Problem

- If a website has several roles just the displaying role's label might not be 
  enough information for administrators. 

Solution

- Add description to user roles.

Examples

- Add user (/admin/people/create)

### Alter forms via ID and views exposed filter by view name and/or display id 

Problem

- The 'placeholder' attributes is not being actively used on Drupal 8.  

Solution

- Alter forms using custom configuration file to all placeholder attribute.

Example

- Search block (<front>)
- Content (/admin/content)


Notes
-----

These examples are just recipes for using the `_config.module` within a
custom module. 

Please feel free to recommended any additional reusable recipes that can be 
added to these examples.


Author/Maintainer
-----------------

- [Jacob Rockowitz](http://drupal.org/user/371407)
