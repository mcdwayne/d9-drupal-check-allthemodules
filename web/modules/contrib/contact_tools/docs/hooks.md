# Hooks

There are several hooks that can be handful in some cases.

### hook_contact_tools_modal_link_options_alter()

```php
/**
 * Implements hook_contact_tools_modal_link_options_alter().
 *
 * Allows you to alter link and url options for modal links.
 */
function hook_contact_tools_modal_link_options_alter(array &$link_options, $key) {
  $link_options['width'] = 600;
  $link_options['dialogClass'] = 'my-special-form';
}
```

### hook_contact_tools_ajax_response_alter() and hook_contact_tools_CONTACT_NAME_ajax_response_alter()

```php
/**
 * Implements hook_contact_tools_ajax_response_alter().
 *
 * Allows modules to alter AJAX response handled by the module. You can fully
 * alter, remove and add new commands to response.
 */
function hook_contact_tools_ajax_response_alter(\Drupal\core\Ajax\AjaxResponse &$ajax_response, $form, Drupal\Core\Form\FormStateInterface $form_state) {
  if ($form_state->isSubmitted()) {
    // This will replace whole form with this text. You can render something to 
    // it!
    $ajax_response->addCommand(new ReplaceCommand('#contact-form-' . $form['#build_id'], t('Thank you for your submission!')));
  }
}

/**
 * Implements hook_contact_tools_CONTACT_NAME_ajax_response_alter().
 *
 * Allows modules to alter AJAX response handled by the module. You can fully
 * alter, remove and add new commands to response.
 *
 * This hook only apply for specified contact form name. You must pass only
 * machine name of contact form. F.e. is form has form_id
 * "contact_message_feedback_form" so form name here is "feedback". In other
 * words, this is bundle name of the contact_message entity.
 */
function hook_contact_tools_CONTACT_NAME_ajax_response_alter(\Drupal\core\Ajax\AjaxResponse &$ajax_response, $form, Drupal\Core\Form\FormStateInterface $form_state) {
  if ($form_state->isSubmitted()) {
    $base_url = \Drupal::request()->getSchemeAndHttpHost();
    $ajax_response->addCommand(new RedirectCommand($base_url . '/submission-complete'));
  }
}
```
