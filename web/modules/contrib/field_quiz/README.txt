Field Quiz
====================

Description
-----------
This module provides a simple field that can be used as a simple quiz or multiple choice questions.
It provides a field with a textfield and an checkbox combined. If added to a content type the editor
of the node can enter multiple answers in the textfields and determine which answers are correct.

This module is for everybody who does not want to use the whole complex system of the quiz module,
but just a simple field for multiple choice questions.


Requirements
------------
Drupal 8.x
See https://www.drupal.org/project/field_quiz for additional requirements.

Installation
------------
1. Copy the entire field_quiz directory the Drupal modules directory.
   Or use drush for this: drush pm-download field_quiz

2. Login as an administrator. Enable the module in the "Administer" -> "Modules"
   Or use drush for this: drush pm-enable field_quiz

3. (Optional) Edit the settings under "Administer" -> "Configuration" ->
   "System" -> "Field Quiz settings"

4. You can now add a field of the type "Field Quiz Question" to any entity.


Usage
-------------
- Add the field to an entity type in the "manage fields" settings of your entity type
- set the number of values or maybe just use unlimited
- create a new entity of your content type
- you can use the title field of the entity to enter the question
- now enter a possible answer in the textfield
- mark the checkbox if this is a correct answer
- push "add another item", to get a new textfield (if the number of values was set above 1)
- enter a new answer in the textfield
- mark the checkbox if this is a correct answer
- continue until all answers are set

Support
-------
Please use the issue queue for filing bugs with this module at
http://drupal.org/project/issues/field_quiz

