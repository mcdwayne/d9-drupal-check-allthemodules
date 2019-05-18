
Contents of this file
---------------------

 * About this module
 * Demo
 * Features
 * Installation
 * Notes


About this Module
-----------------

Adds a jump menu to the toolbar that allows administrators to view the current 
page's entity and to quickly go to a different entity.

The entity jump menu is specifically helpful when URL aliases are setup for 
nodes, users, and terms because the system paths for these entities, 
which includes the entity type and id ,is not immediately visible in the 
browser's address bar.


Demo
----

> Evaluate this project online using [simplytest.me](https://simplytest.me/project/entity_jump_menu).


Features
--------

- Adds entity type (select menu) and entity id (text field) to toolbar.  

- Provides an 'Entity jump menu' block.  

- Includes 'Use the entity jump menu toolbar widget' permission.
 
 
Installation
------------

1. Copy/upload the `entity_jump_menu.module` to the modules directory of your 
   Drupal installation.

2. Enable the `Entity Jump Menu' module in 'Extend'. (/admin/modules)

3. Set 'Entity Jump Menu' permissions. 
   (/admin/people/permissions#module-entity_jump_menu)

4. (optional) Place the 'Entity jump menu' block on all pages.
   (/admin/structure/block)


Notes
-----

Currently, only Node, User, and Term entities are supported because these are 
the most common use-cases for the entity jump menu. If you need support for 
other entity types please open a ticket.
 
Due to the fact that each browser renders forms differently and some themes, 
including Seven, have different ways of styling form elements, the entity
jump menu's look and alignment will change from browser to browser.  Any
suggestions and/or patches that address this challenge/issue are welcome, 
please make sure to test them across multiple browsers.


Known Issues
------------

- Entity jump menu block does not display errors because the form is embedded 
  in a block which which is loaded via a placeholder.

    - The user login block has the same issue.   


Author/Maintainer
-----------------

- [Jacob Rockowitz](http://drupal.org/user/371407)
