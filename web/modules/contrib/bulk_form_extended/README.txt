
-- SUMMARY --

The Bulk Form Extended module enables additonal options for changing the
display of the form and checkboxes of Views Entity Bulk select field.

The module overrides the Node operations bulk form, Bulk update (User), Bulk
update (Entity) to allow additional options on these fields.

-- REQUIREMENTS --

Drupal Views, Drupal Node, Drupal User


-- INSTALLATION --

* Install as usual, see http://drupal.org/node/895232 for further information.


-- CONFIGURATION --

* Customize a bulk form field in Drupal

  - Show Toggle All Button?
      Show a button which allows you to select all the items on the list. If all
      items are already selected, the button will become a deselect button.

  - Display a custom empty select message?
      When no options are selected and the form is submitted, change the return
      error message.

  - Checkbox Label
      Create a label for the checkboxes. Tokens can be used.


  - Replace the dropdown field with a single button if only one action is
    available.
      When this option is selected and only a single action is available to the
      user, the dropdown box and submit button will be replaced with a single
      button, labeled with the single action which is available.

-- CONTACT --

Current maintainers:
* Anthony Cooper (_Coops) - https://www.drupal.org/u/coops_
