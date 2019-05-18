# Multilanguage Form Display

## Purpose
This module will attempt to make your multi-language editing experience a bit easier. By enabling this module and following its simple steps, it will display on one entity form (i.e.: Node Edit form) all the translatable fields available for that content type. It will perform all appropriate validation and submission according to Drupal Best Practices.

## How it works
By hooking into a node entity and modifying the edit form with all viable translatable fields, the module inserts all the selected language fields into the form according to the placement of the Multilingual Form Display Field. You have a few settings to play with for visibility and Quality of Life stuff. (n.b.: more improvement and options to come.)

The module should play nicely with [Display Suite](https://www.drupal.org/project/ds) and [Panels](https://www.drupal.org/project/panels) since we've done nothing out of the ordinary here.

## History
The original module was called the [Dual Language Editor](https://www.uottawa.ca/uoweb/en/user-guide/exploring-dual-language-editor) (DLE). It was made for Drupal 7 and had a lot of workarounds to try and solve a problem a few of us had trouble resolving.

Several years later, with the introduction of Drupal 8 and the Object Oriented nature of the Symfony framework it leverages, we can now better leverage the various APIs and drastically reduce the lines of code from the previous version of the module.

## Dependencies from core
  - content_translation
  - language

## How To Use
The module is quite simple at the moment.

  - Install and Enable the module
  - Ensure you've got your dependencies enabled
  - Make sure you've got at __least two languages__ on your site
  - From the Content Language admin page (<code>admin/config/regional/content-language</code>),
 select the content types and their fields which will be translatable
  - From your entity page or content types admin page (<code>admin/structure/types</code>), choose to <strong>manage fields</strong>
  - Add the Multilingual Form Display field (no settings right now)
  - In the <strong>Manage Form Display</strong> page, choose where in the form you would like the translatable fields to be displayed
  - The <strong>formatter</strong> does nothing at present

That should be it for now. You'll now see, as in the image attached to this module, all your Translatable fields for all languages other than the default one you are visiting the site with.

## Note
This module works really nicely with a multi-column layout. You can active these by using the field_layout module and picking a layout for the form. Then, by placing the default fields in one column and the <strong>multilingual form display</strong> in a second column, you get a nice side-by-side editing experience.

So far, this has been tested with <strong>under 10 languages</strong> at a time with content types that have a small number of fields which are translatable <strong>(less than five)</strong>. Loading several more languages and multiplying the number of fields which are translatable by that will cause the form to have a very large number of fields to render. So be careful not to overload your browser. Especially if any of the fields are using some sort of Text Editor like <strong>CKEditor</strong> or <strong>TinyMCE</strong>. Those will greatly impact the UX of the form. We are aware of this and will be addressing it as we move forward.

## ** Active Development **
Please be aware, this project is under active development and will be changing frequently. Once, it has stabilized, it will be made available in the normal course of releases.

### Roadmap
  - ~~Add widget settings~~
  - ~~Add formatter settings~~
  - ~~Add layout settings~~
  - ~~Add node rendering settings~~
  - ~~Add permissions~~
  - When using more than one MFD field, have one of the field be the "language swapping"
 field
  - Add field formatter template file

### Wishlist
  - Add Drupal 7 version
  - Add AJAX / Lazy loading of languages
  - Add hot-swappable language loading on form (with auto-saving)
  - Add yaml configuration file

### The new approach

We're using the FieldPlugin classes approach.

### The previous method

(**_n.b.: all this was several iterations of code ago_**)

To achieve this goal, we are focusing on altering a few forms and calling on the power of OOP to re-purpose some fields, move them around and eventually get them to save back to their proper storage definitions.

We do so by calling on each translation, getting the FormState to play nicely with each translation and get Drupal to sort itself out without using a heavy hammer approach at getting and setting values. We leave the power of the classes to manage this. Go Methods!!

To understand what is going on here, just follow the trail of breadcrumbs. We start at the hook_widget_form_alter(). Here we begin the process for each field which is permitted to be translated:

- From the translated entity we get the appropriate field as an item.
- We tell the FormState not to recurse as we will be asking the widget for a form
- create a basic form
- Tell the FormState to shift language state
- get the widget's form
- iterate through that until we have all the languages set and saved in the FormState

Now we can more onto the hook_node_form_alter() to get the NodeForm modifications done. These will ensure that we have what we need to Render a form with the proper named fields.

Since we told the widget we are being used by us (#multiform_display_use) we don't need to repeat some of the housework we did in the widget alter function. Now we can just start sweeping through the form object, cleaning it up for each language instance and getting the proper values to the stored in each translated entity.

The only caveat is we need to unset a few values and clean up a few #field_name and #parent properties to ensure no overlapping naming conventions. Once this is done, we're home free. The remaining functionality (validation and submission) is handled by their appropriate objects. We only have to ensure that on submit
we cycle through the language fields we've created and force their values into their none languaged equivalents in their appropriate translation entity->save().

## Module project page:
http://drupal.org/project/mfd (TBD)

## Documentation page (D8 version):
http://drupal.org/node/##


### Configuration

1. Its really quite simple, install, enable more than one language and activate the entity's translatable fields you wish to use this feature with.. 

## Module Creator

wilco - https://www.drupal.org/u/wilco