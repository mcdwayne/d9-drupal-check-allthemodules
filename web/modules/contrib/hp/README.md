This module integrates the Human Presence API to protect Drupal forms.

More info: https://humanpresence.io

Module configuration:
1. Enable the module.
2. Go to /admin/config/development/human-presence to add your HP API key. To get one, [register at Human Presence](https://www.humanpresence.io/sign-up.php#Drupal).
3. Check the "Enable Human Presence monitoring via its JavaScript user interaction tracker." checkbox.
4. Go to /admin/config/development/human-presence/protected-forms tab and click "Add protected form".
5. Enter the form ID of the form you wish to protect into the ID field. Alternatively, you can provide a regular expression to cover several forms at once, but this might have performance implications. (In this case the ID field is only used as the protected form definition ID and not as a form ID.) To find the form ID look at the form HTML code and look for a hidden input element with name "form_id". The form ID is the value attribute of this element. E.g. the form ID from `<input data-drupal-selector="edit-hp-form-strategy-add-form" type="hidden" name="form_id" value="hp_form_strategy_add_form">` is `hp_form_strategy_add_form`. 
6. Select a plugin and configure it.

The module ships with two plugins:
1. The "Fail validation" plugin simply does not let the user submit the form if Human Presence thinks it's not submitted by a human. 
2. The "CAPTCHA" plugin is only available if the CAPTCHA module is enabled. When Human Presence thinks the form submitter might not be human, it reloads the form with a CAPTCHA. It needs both CAPTCHA and Human Presence configured for the form to work.
