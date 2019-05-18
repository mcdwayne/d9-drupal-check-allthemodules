<?php

/**
 * Write default values of additional variables to module configuration.
 */
function insert_update_8101(array &$sandbox) {
  $configFactory = \Drupal::configFactory();
  $config = $configFactory->getEditable('insert.config');

  // It is possible to access the module configuration page and save
  // configuration after applying the new code while not having run the update.
  // So, the new variables might have been registered already, but without their
  // default value.

  if ($config->get('absolute') === null) {
    $config->set('absolute', FALSE);
  }

  if ($config->get('file_field_images_enabled') === null) {
    $config->set('file_field_images_enabled', FALSE);
  }

  if ($config->get('widgets') === null) {
    $config->set('widgets', ['file' => [], 'image' => []]);
  }

  // Since figuring out the default value for compatible widgets is a bit
  // tricky, set the default values if no value is set. This state may occur
  // when module configuration was saved before running the update. There is no
  // major reason to not have at least one value for compatible widgets per
  // Insert method.
  if (count($config->get('widgets.file')) === 0) {
    $config->set('widgets.file', ['file_generic']);
  }
  if (count($config->get('widgets.image')) === 0) {
    $config->set('widgets.image', ['image_image']);
  }

  if (!is_array($config->get('css_classes')['file'])) {
    $cssClasses = $config->get('css_classes');
    $config->set(
      'css_classes',
      [
        'file' => explode(' ', $cssClasses['file']),
        'image' => explode(' ', $cssClasses['image'])
      ]
    );
  }

  if ($config->get('file_extensions') === null) {
    $config->set('file_extensions', ['audio' => ['mp3'], 'video' => ['mp4']]);
  }

  $config->save(TRUE);
}
