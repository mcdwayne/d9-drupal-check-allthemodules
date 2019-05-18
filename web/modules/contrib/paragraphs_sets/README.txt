-- SUMMARY --

The "Paragraphs Sets" module allows to create sets of paragraphs added to an
entity by default or selected while creating the entity.


-- REQUIREMENTS --

Obviously you'll need to install the "Paragraphs" module.


-- INSTALLATION --

Install as usual, see
https://www.drupal.org/documentation/install/modules-themes/modules-8 for
further information.


-- WIDGETS --

"Paragraphs Sets" currently provides one widget in addition to "Paragraphs".

  * "Paragraphs Classic (sets)": like "Paragraphs Classic" but with the option
    to configure a default set and select the paragraphs set on entity creation.


-- CONFIGURATION EXAMPLES --

There is no UI (yet?) for creating paragraph sets so you have to create the
config entities manually. Setting default values is limited (and tested only)
for primitive field types. Setting complex field values requires implementing
hook_paragraphs_set_data_alter(). See paragraphs_sets.api.php for all hooks.

  * Create a set containing a single paragraph of type "text":
    <code>
    id: ps_example_text
    label: 'Simple text (empty)'
    description: 'Simple text paragraph without values'
    paragraphs:
      -
        type: text
        data: {  }
    </code>
  * Create a set containing multiple paragraphs with default values:
    <code>
    id: ps_example_text_multiple
    label: 'Multiple paragraphs'
    description: 'Multiple paragraphs with default values'
    paragraphs:
      -
        type: text
        data:
          field_headline: 'First item'
          field_content: '<p>You may add HTML, too!</p>'
      -
        type: text_with_image
        data:
          field_headline: 'Second item'
      -
        type: text
        data:
          field_content: '<p>This is another text paragraph.</p>'
    </code>


-- CONTACT --

Current maintainers:
* Stefan Borchert (stborchert) - http://drupal.org/user/36942

This project has been sponsored by:

  * undpaul
    Drupal experts providing professional Drupal development services.
    Visit http://www.undpaul.de for more information.
