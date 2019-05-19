# Simple content for Drupal 8

The code is now on drupal.org: https://www.drupal.org/project/simple_content

This module exposes a simple entity that is not translatable or revisionable. This will stay like this always, so do not
open a feature request for revisions or translations. You can create multiple simple content types (bundles).

Every simple content type can be embedded in a block. A block is also available to render an existing simple content 
entity using autocomplete to select.

Think of it as a light version of Fieldable Panels Panes for Drupal 8.

Why not use custom block content: Custom block content is not designed to be rendered outside the block system, so this 
is an alternative, and also solves the scalability problem that custom blocks have.

Use cases:

- embed anywhere in the site via blocks
- layout builder content
- render with entity reference
- render with views
- your case

Want to alter the form when presented in the layout builder context ?

```php
/**
 * Implements hook_form_FORM_ID_alter().
 */
function example_form_layout_builder_add_block_alter(&$form, FormStateInterface $form_state) {
  _example_form_layout_builder_block_alter($form, $form_state);
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function example_form_layout_builder_update_block_alter(&$form, FormStateInterface $form_state) {
  _example_form_layout_builder_block_alter($form, $form_state);
}

/**
 * Alters the simple content form in layout context.
 * 
 * @param $form
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 */
function _example_form_layout_builder_block_alter(&$form, FormStateInterface $form_state) {
  if (isset($form['settings']['simple_content_form'])) {
    $form['settings']['admin_label']['#access'] = FALSE;
    $form['settings']['simple_content_form']['#process'][] = 'example_simple_content_layout_process_form';
  }
}

/**
 * Process function for simple content. Runs when the entity form is presented
 * in the layout builder context.
 *
 * @param array $element
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *
 * @return array
 */
function example_simple_content_layout_process_form(array $element, FormStateInterface $form_state) {
  $element['user_id']['#access'] = FALSE;
  $element['status']['#access'] = FALSE;
  return $element;
}

```

Inspired by https://www.drupal.org/project/drupal/issues/2957425 (Explore the concept of Custom Block re-usability)
We needed something right now without having to wait for the core implementation.

Related Drupal Core issues:

- https://www.drupal.org/project/drupal/issues/2859197  
  Document that block_content entities are not designed to be displayed outside of blocks
- https://www.drupal.org/project/drupal/issues/2704331  
  Ability to display block_content entities independently, also outside of Blocks
- https://www.drupal.org/project/drupal/issues/2940755  
  block_content block derivatives do not scale to thousands of block_content entities

Currently on GitHub - might sync back to Drupal.org when there's a lot of interest.
