-- SUMMARY --

Tired of the non-descriptive entity reference autocomplete labels? This module enhances the core labels with the entity's machine name, which is especially useful if a referenced entity has the same name as another one being referenced.


-- REQUIREMENTS --

You must have the entity_reference core module installed.


-- INSTALLATION --

* Install as usual as per http://drupal.org/node/895232.

-- EXAMPLE USAGE --

* Create an entity reference field that references block configuration, however this could be another entity type.
* On the field's edit page, select "Default (Descriptive)" as the reference method.
* Make sure the field uses an autocomplete widget on the form display settings.
* On your edit page, you can search via typeahead like a normal autocomplete field, but the format will be "Entity Label [machine_name]" instead of just "Entity Label".

-- CONTACT --

Current maintainers:
* David Lohmeyer (vilepickle) - https://www.drupal.org/u/vilepickle
