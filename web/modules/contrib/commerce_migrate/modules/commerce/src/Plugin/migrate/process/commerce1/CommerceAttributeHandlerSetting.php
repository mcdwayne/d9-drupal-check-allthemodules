<?php

namespace Drupal\commerce_migrate_commerce\Plugin\migrate\process\commerce1;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Adjusts the fields settings for attributes.
 *
 * @MigrateProcessPlugin(
 *   id = "commerce1_attribute_handler_setting"
 * )
 */
class CommerceAttributeHandlerSetting extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If this is an attribute field, add the handler_settings.
    if (('commerce_product' === $row->getSourceProperty('entity_type')) &&
      ('taxonomy_term_reference' === $row->getSourceProperty('type')) &&
      ('options_select' === $row->getSourceProperty('widget')['type'])) {
      $new_handler_settings['target_bundles'][] = $row->getSourceProperty('bundle');
      $settings = $row->getDestinationProperty('settings');
      $handler_settings = isset($settings['handler_settings']) ? $settings['handler_settings'] : [];
      return array_merge($handler_settings, $new_handler_settings);
    }
    else {
      throw new MigrateSkipProcessException();
    }
  }

}
