Bibcite Footnotes
=================

Contents
--------

 * Introduction
 * Installation
 * Use
 * Current Maintainers

Introduction
------------

Provides a CKEditor plugin that lets a user select from a list of citations which
appear in a formatted list at the bottom of the text that contains endnotes
and references.

Installation
------------

### Works Cited field

1. Go to Administration > Structure > Content Types and edit the type that will include Reference Footnotes
2. In the Manage Fields tab, press Add Field.
3. In "Re-use an existing field" select "Entity reference: field_bibcite_fn_works_cited".
4. Enter an appropriate label and press 'Save and continue'.
5. On the next page select all the items in "Reference type" that should be available to choose from as footnotes.
6. Customize the field under "Manage form display" and "Manage display", typically you'll want to 
   place the Works Cited field directly under the Body field. See below for configuring with 
   Inline Entity Form.
7. In "Manage display", select "Rendered entity" in the Format drop-down for the Works Cited field.
8. Press the Settings icon and under "View mode" choose "Citation" - a new view mode created 
   by this module.

#### Inline Enity Form

It's recommended to also install [Inline Entity Form][1] which allowes a user to
create new references directly in the node edit form.

1. In "Entity Form Display", select ''Inline Entity Form - complex".
2. Press the settings button in the rightmost column.
3. Enabling  "Allow users to add new reference entities" and
   "Allow users to add existing reference entities" is recommended. 

[1]: https://www.drupal.org/project/inline_entity_form

### CKEditor filter and toolbar button

1. Enable the module.
2. Go to Administration > Configuration > Content authoring > Text formats and editors.
 3. Edit the text format you want to add Reference Footnotes to
4. Enable the Reference Footnotes Filter
5. Drag the Reference Footnotes button into the active buttons toolbar.
6. Configure the options for the Reference Footnotes Filter.

If the 'Allowed HTML tags' filter is enabled add these HTML tags to the allowed list:

```html
    <a class href id> <div class id> <span class id> <ol class id type> 
	<sup> <li class id>
```

Use
---

See the documentation for [Bibliography and Citation - Import][2] module for how to
add citations exported from a citation management system like RefWorks or EndNote.

[2]: https://www.drupal.org/project/bibcite

### Adding a Works Cited item

If Inline Entity Form is enabled, an author can create new citations or add references to 
previously-imported or created references directly with the 'Add new reference' and 
'Add existing reference' buttons.

With Inline Entity Form, newly-added citations are immediately selectable in the 
Reference Footnotes dialog.

If not using the inline entity form, you can select previously imported or created references 

When you edit the node again, and you click on the Reference Footnote editor toolbar button,
a list of citations will be available to choose from.

### Citation Formatting

The Works Cited field will use the Default style selected in the 
Bibliography & Citation administration pages at Administration > Configuration > 
Bibliography & Citation > Settings in the Processing tab under Processor.'

Current Maintainers
-------------------

 * Alexander O'Neill (https://www.drupal.org/u/alxp)

Sponsors
--------

 * This work is supported by the [University of Prince Edward Island Robertson Library](https://library.upei.ca).