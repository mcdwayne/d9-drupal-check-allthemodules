<?php

namespace Drupal\migrate_process_url\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;

/**
 * Generate an array suitable for consumption by field_link.
 *
 * @MigrateProcessPlugin(
 *   id = "field_link_generate",
 *   handle_multiples = TRUE
 * )
 *
 */
class FieldLinkGenerate extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Assure that the input is an array.
    $in = is_array($value) ? $value : [$value];

    // Get all the titles
    $titles = $this->fetchProperty($row, 'title_source', '');

    // Process each item
    $out = [];
    foreach ($in as $key => $url) {
      // Build the array.
      $out[$key] = [
        'url' => $url,
        'title' => is_array($titles) && isset($titles[$key]) ? $titles[$key] : '',
        'attributes' => $this->fetchAttrs($row),
      ];
    }

    return $out;
  }

  /**
   * Fetch the attributes.
   *
   * @param \Drupal\migrate\Row $row
   *   The migration row.
   *
   * @return mixed|string|null
   *   The serialized attributes value.
   */
  protected function fetchAttrs(Row $row) {
    $attrs = $this->fetchProperty($row, 'attr_source', []);

    // If the value is unserialized, return it serialized.
    if (@unserialize($attrs) === FALSE) {
      return serialize($attrs);
    }

    // Otherwise, just pass it on through.
    return $attrs;
  }

  /**
   * Fetch a source or destination property from the row.
   *
   * @param \Drupal\migrate\Row $row
   *   The migration row.
   * @param $conf_key
   *   The process plugin configuration key
   * @param null $default
   *   The default value to return if the fetch values. Optional. Defaults to  NULL.
   *
   * @return mixed|null
   *   The property value if found, otherwise the value of $default.
   */
  protected function fetchProperty(Row $row, $conf_key, $default = NULL) {
    if (empty($this->configuration[$conf_key])) {
      return $default;
    }

    $property_name = $this->configuration[$conf_key];

    if ($row->hasSourceProperty($property_name)) {
      return $row->getSourceProperty($property_name);
    }

    // Chop off the '@' for self-referential destination values.
    if (substr($property_name, 0, 1) == '@') {
      $property_name = substr($property_name, 1);
    }

    if ($row->hasDestinationProperty($property_name)) {
      return $row->getDestinationProperty($property_name);
    }

    return $default;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return TRUE;
  }
}
