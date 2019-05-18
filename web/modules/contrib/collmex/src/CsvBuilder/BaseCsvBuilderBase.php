<?php

namespace Drupal\collmex\CsvBuilder;

use Drupal\Core\Datetime\DrupalDateTime;

abstract class BaseCsvBuilderBase implements BaseCsvBuilderInterface {

  /**
   * @return array
   */
  protected function getFieldDefinitions() {
    // @todo Consider finding a better way to handle this special key.
    return ['type_identifier' => 'c99'];
  }

  /**
   * Get collmex definition.
   *
   * @param string $key
   * @return string
   */
  private function getFieldDefinition($key) {
    $defs = $this->getFieldDefinitions();
    if (!isset($defs[$key])) {
      throw new \LogicException("Invalid key: $key");
    }
    $def = $defs[$key];
    return $def;
  }

  public function getFieldType($key) {
    $def = $this->getFieldDefinition($key);
    $type = substr($def, 0, 1);
    return $type;
  }

  public function getFieldLength($key) {
    $def = $this->getFieldDefinition($key);
    $length = substr($def, 1);
    return $length;
  }

  protected function makeCsv($values) {
    $values += $this->getDefaultValues();

    // Fix decimal separator.
    array_walk($values, function (&$value, $key) {
      $type = $this->getFieldType($key);
      if ($type === self::TYPE_NUM || $type === self::TYPE_MONEY) {
        $value = strtr($value, ['.' => ',']);
      }
    });

    // Convert dates.
    array_walk($values, function (&$value, $key) {
      $type = $this->getFieldType($key);
      if ($type === self::TYPE_DATE && $value && $value !== '(NULL)') {
        if (is_numeric($value)) {
          // Some timestamps fail, like 1544400347
          $value = "@$value";
        }
        $value = (new DrupalDateTime($value))->format('Ymd');
      }
    });

    // Pad with (NULL]s. Note that this does not set type_identifier.
    $values += array_fill_keys(array_keys($this->getFields()), '(NULL)');

    $collmexObject = $this->makeCollmexObject($values);
    $csv = $collmexObject->getCsv();
    return $csv;
  }

  /**
   * @param array $values
   *
   * @return \MarcusJaschen\Collmex\Type\TypeInterface
   */
  abstract protected function makeCollmexObject(array $values);

}
