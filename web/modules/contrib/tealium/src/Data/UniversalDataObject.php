<?php

namespace Drupal\tealium\Data;

/**
 * Class UniversalDataObject.
 *
 * Store variable name-value pairs for a Tealium Universal Data Object.
 */
class UniversalDataObject implements UniversalDataObjectInterface {

  private $allDataSourceValues = [];

  /**
   * {@inheritdoc}
   */
  public function __construct($variables = []) {
    $this->setAllDataSourceValues($variables);
  }

  /**
   * Set all data source variables.
   *
   * @return $this
   */
  public function setAllDataSourceValues($dataVariables) {
    // @todo: throw Invalid Argument Exception if parameters not correct type
    if ($dataVariables instanceof \stdClass) {
      $properties = get_object_vars($dataVariables);
      foreach ($properties as $name => $value) {
        $this->setDataSourceValue($name, $value);
      }
    }
    elseif (is_array($dataVariables)) {
      $this->allDataSourceValues = $dataVariables;
    }

    return $this;
  }

  /**
   * Gets all data source variables' values.
   *
   * @return array
   *   All variables.
   */
  public function getAllDataSourceValues() {
    return $this->allDataSourceValues;
  }

  /**
   * {@inheritdoc}
   */
  public function setDataSourceValue($name, $value) {
    $name = strval($name);

    if (strlen($name) !== 0) {
      $this->allDataSourceValues[$name] = $value;
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataSourceValue($name) {
    // @todo: Throw Invalid Argument Exception if param not a string.
    $value = NULL;

    if (array_key_exists($name, $this->allDataSourceValues)) {
      $value = $this->allDataSourceValues[$name];
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function unsetDataSourceValue($name) {

    if (array_key_exists($name, $this->allDataSourceValues)) {
      unset($this->allDataSourceValues[$name]);
    }

    return $this;
  }

}
