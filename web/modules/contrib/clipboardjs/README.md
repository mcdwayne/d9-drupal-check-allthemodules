INTRODUCTION
------------
Integrates the Clipboard.js library with Drupal.

REQUIREMENTS
------------
 * clipboard.js library - https://github.com/zenorocha/clipboard.js/archive/master.zip

INSTALLATION
------------
1. Download and extract the clipboard.js plugin, rename extracted folder to
   "clipboard" and copy it into "sites/all/libraries". The plugin should
   now be located at "sites/all/libraries/clipboard/src/clipboard.js". You can
   also just use 'drush cbdl' to automatically download the library.

USAGE
-----
The Clipboard.js can be added to any text field by visiting the manage display
page for the entity and and choosing Clipboard.js as the field formatter.

Custom text fields can also use clipboard.js using the form api or in a render
array using the theme function to display the element:

    function example_form($form, $form_state) {
      $form = array();
    
      
      // Load clipboard.js library.
      libraries_load('clipboard');
    
      
      // Clipboard settings.      
      $theme_variables = array(
        'text' => t('This is the text to be copied...'), 
        'alert_style' => 'tooltip', 
        'alert_text' => 'Copy was successful!', 
        'button_label' => 'Click to Copy',
      );

      
      // Build the form or render element using the helper function:
      $form['textfield'] = theme('clipboardjs', $theme_variables);
    
      
      return $form;
    }


CREDITS
-------
* Norman Kerr - [kenianbei](https://drupal.org/user/778980)
