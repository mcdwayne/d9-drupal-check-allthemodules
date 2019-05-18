<?php

namespace Drupal\search_api_swiftype\SwiftypeDocument;

use Drupal\search_api\Plugin\search_api\data_type\value\TextValueInterface;
use Drupal\search_api_swiftype\SwiftypeEntity;

/**
 * Defines a SwiftypeDocument.
 */
class SwiftypeDocument extends SwiftypeEntity implements SwiftypeDocumentInterface {

  /**
   * List of fields in the document.
   *
   * @var array
   */
  protected $fields = [];

  /**
   * {@inheritdoc}
   */
  public function getExternalId() {
    return $this->data['externalId'];
  }

  /**
   * {@inheritdoc}
   */
  public function setExternalId($id) {
    $this->data['externalId'] = $id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFields() {
    return $this->fields;
  }

  /**
   * {@inheritdoc}
   */
  public function addField($name, $value = NULL, $type = 'string') {
    if (!is_array($value)) {
      $value = [$value];
    }
    switch ($type) {
      case 'date':
        $values = [];
        foreach ($value as $item_value) {
          if (is_numeric($item_value)) {
            // Format date.
            $item_value = date(\DateTime::ISO8601, $item_value);
          }
          $values[] = $item_value;
        }
        $value = $values;
        break;

      case 'text':
        $values = [];
        foreach ($value as $item_value) {
          if ($item_value instanceof TextValueInterface) {
            $values[] = $item_value->toText();
          }
          else {
            $values[] = $item_value;
          }
        }
        $value = $values;
        break;
    }
    if ('m_' !== substr($name, 0, 2)) {
      $value = reset($value);
    }
    $this->fields[$name] = (object) [
      'name' => $name,
      'type' => $this->mapDataType($type),
      'value' => $value,
    ];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeField($name) {
    unset($this->fields[$name]);
    return $this;
  }

  /**
   * Map a search_api data type to a Swiftype field type.
   *
   * @param string $type
   *   The type to map.
   *
   * @return string
   *   The mapped type.
   */
  protected function mapDataType($type) {
    $map = [
      'boolean' => 'enum',
      'decimal' => 'float',
    ];

    return isset($map[$type]) ? $map[$type] : $type;
  }

}
