<?php

namespace Drupal\drupal_yext\Yext;

use Drupal\drupal_yext\traits\Singleton;
use Drupal\drupal_yext\traits\CommonUtilities;

/**
 * Represents Yext field mapping.
 */
class FieldMapper {

  use Singleton;
  use CommonUtilities;

  /**
   * Get info about all fields (custom or standard) which can be mapped.
   *
   * @return array
   *   Array of fields, each being an array with keys "name" and "type".
   *
   * @throws Exception
   */
  public function allFields() : array {
    $return = [];
    $return[] = [
      'name' => $this->yext()->uniqueYextIdFieldName(),
      'type' => 'plaintext',
    ];
    $return[] = [
      'name' => $this->yext()->uniqueYextLastUpdatedFieldName(),
      'type' => 'plaintext',
    ];
    $return[] = [
      'name' => $this->bio(),
      'type' => 'formatted',
    ];
    $return[] = [
      'name' => $this->headshot(),
      'type' => 'image',
    ];
    $return[] = [
      'name' => $this->raw(),
      'type' => 'long text',
    ];
    foreach ($this->customFieldInfo() as $custom) {
      if (!empty($custom[1])) {
        $return[] = [
          'name' => $custom[1],
          'type' => 'plaintext',
        ];
      }
    }
    return $return;
  }

  /**
   * The bio field name.
   *
   * @return string
   *   Bio field name.
   */
  public function bio() : string {
    return $this->configGet('field_mapping_bio', '');
  }

  /**
   * Info about custom fields as entered into /admin/config/yext/yext.
   *
   * @return array
   *   Array of custom fields, each custom field being itself represented
   *   as an array with keys:
   *     0 => the Yext custom field ID, for example 12345.
   *     1 => the Drupal field if possible, for example NULL, an empty string,
   *          or field_drupal_field.
   *     2 => a field description if it exists.
   *
   * @throws Exception
   */
  public function customFieldInfo() : array {
    $return = [];
    $raw_custom_field_info = $this->fieldMapping();
    $rows = str_getcsv($raw_custom_field_info, "\n");
    foreach ($rows as $row) {
      $return[] = str_getcsv($row);
    }
    return $return;
  }

  /**
   * Get errors.
   *
   * @return array
   *   An array of items, each should have the "text" key which is the text of
   *   the error.
   */
  public function errors() : array {
    $return = [];
    try {
      $type = $this->yext()->yextNodeType();
      $this->nodeTypeLoad($type);
      $field_definitions = $this->fieldDefinitions('node', $type);
      foreach ($this->allFields() as $fieldinfo) {
        if (!in_array($fieldinfo['name'], array_keys($field_definitions))) {
          $return[] = [
            'text' => $this->t('The @t field @f does not exist for the node type @n; please create it.', [
              '@t' => $fieldinfo['type'],
              '@f' => $fieldinfo['name'],
              '@n' => $type,
            ]),
          ];
        }
      }
    }
    catch (\Throwable $t) {
      $return[] = [
        'text' => $t->getMessage(),
      ];
    }
    return $return;
  }

  /**
   * The field mapping between Yext and Drupal fields.
   *
   * @return string
   *   A CSV-type value such as:
   *     "1234","field_drupal_field","description"
   *     "2345",,"this is not mapped to drupal but exists in yext"
   *
   * @throws \Throwable
   */
  public function fieldMapping() : string {
    return $this->configGet('field_mapping', '');
  }

  /**
   * The geo field name (requires the geofield module).
   *
   * @return string
   *   Headshot field name.
   */
  public function geo() : string {
    return $this->configGet('field_mapping_geo', '');
  }

  /**
   * The headshot field name.
   *
   * @return string
   *   Headshot field name.
   */
  public function headshot() : string {
    return $this->configGet('field_mapping_headshot', '');
  }

  /**
   * The raw info field name.
   *
   * @return string
   *   Raw info field name.
   */
  public function raw() : string {
    return $this->configGet('field_mapping_raw', '');
  }

  /**
   * Set the bio field name.
   *
   * @param string $value
   *   Bio field name.
   */
  public function setBio(string $value) {
    $this->configSet('field_mapping_bio', $value);
  }

  /**
   * Set the geofield field name.
   *
   * @param string $value
   *   Geofield field name.
   */
  public function setGeo(string $value) {
    $this->configSet('field_mapping_geo', $value);
  }

  /**
   * Set field mapping between Yext and Drupal fields.
   *
   * @param string $mapping
   *   A CSV-type value such as:
   *     "1234","field_drupal_field","description"
   *     "2345",,"this is not mapped to drupal but exists in yext".
   *
   * @throws \Throwable
   */
  public function setFieldMapping(string $mapping) {
    $this->configSet('field_mapping', $mapping);
  }

  /**
   * Set the headshot field name.
   *
   * @param string $value
   *   Headshot field name.
   */
  public function setHeadshot(string $value) {
    $this->configSet('field_mapping_headshot', $value);
  }

  /**
   * Set the raw data field name.
   *
   * @param string $value
   *   Raw data field name.
   */
  public function setRaw(string $value) {
    $this->configSet('field_mapping_raw', $value);
  }

}
