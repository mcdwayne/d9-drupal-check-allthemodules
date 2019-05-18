<?php

namespace Drupal\nodeletter\Entity;

class TemplateVariableSetting {

  /**
   * Weight of this entity.
   *
   * @var int
   */
  protected $weight;

  /**
   * Template variable name.
   *
   * @var string
   */
  protected $variable_name;

  /**
   * Field machine name.
   *
   * @var string
   */
  protected $field;

  /**
   * Field formatter machine name.
   *
   * @var string
   */
  protected $formatter;

  /**
   * Settings of field formatter.
   *
   * @var array
   */
  protected $formatter_settings = [];

  /**
   * Third party settings of field formatter.
   *
   * @var array
   */
  protected $formatter_third_party_settings = [];


  /**
   * @param array $values
   * @return static
   */
  public static function fromArray(array $values) {
    $v = new self();
    $v->setWeight($values['weight'])
      ->setVariableName($values['variable_name'])
      ->setField($values['field'])
      ->setFormatter($values['formatter'])
      ->setFormatterSettings($values['formatter_settings']);
    return $v;
  }

  public function getWeight() {
    return $this->weight;
  }

  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  public function getVariableName() {
    return $this->variable_name;
  }

  public function setVariableName($name) {
    $this->variable_name = $name;
    return $this;
  }

  public function getField() {
    return $this->field;
  }

  public function setField($field_machine_name) {
    $this->field = $field_machine_name;
    return $this;
  }

  public function getFormatter() {
    return $this->formatter;
  }

  public function setFormatter($formatter_plugin_id) {
    $this->formatter = $formatter_plugin_id;
    return $this;
  }

  public function getFormatterSettings() {
    return $this->formatter_settings;
  }

  public function setFormatterSettings(array $settings) {
    $this->formatter_settings = $settings;
    return $this;
  }


  public function toArray() {
    return [
      'variable_name' => $this->getVariableName(),
      'weight' => $this->getWeight(),
      'field' => $this->getField(),
      'formatter' => $this->getFormatter(),
      'formatter_settings' => $this->getFormatterSettings(),
    ];
  }

}
