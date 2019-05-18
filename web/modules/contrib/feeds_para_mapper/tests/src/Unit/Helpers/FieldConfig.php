<?php
namespace Drupal\Tests\feeds_para_mapper\Unit\Helpers;


class FieldConfig {
  /**
   * @var string
   */
  public $label;

  /**
   * @var string
   */
  public $name;

  /**
   * @var string
   */
  public $type;

  /**
   * @var int
   */
  public $id;

  /**
   * @var int
   */
  public $cardinality;

  /**
   * @var array
   */
  public $settings;

  /**
   * @var string
   */
  public $host_type;

  /**
   * @var string
   */
  public $host_bundle;

  /**
   * @var string
   */
  public $host_field;

  /**
   * @var int
   */
  public $host_id;

  /**
   * @var array
   */
  public $paragraph_ids;

  /**
   * FieldConfig constructor.
   * @param string $label
   * @param string $name
   * @param string $type
   * @param int $id
   * @param int $cardinality
   * @param array $settings
   * @param array $paragraph_ids
   * @param string $host_type
   * @param string $host_bundle
   * @param string $host_field
   * @param int $host_id
   */
  public function __construct($label, $name, $type, $id, $cardinality, array $settings,  $paragraph_ids, $host_type, $host_bundle, $host_field, $host_id)
  {
    $this->label = $label;
    $this->name = $name;
    $this->type = $type;
    $this->id = $id;
    $this->cardinality = $cardinality;
    $this->settings = $settings;
    $this->paragraph_ids = $paragraph_ids;
    $this->host_type = $host_type;
    $this->host_bundle = $host_bundle;
    $this->host_field = $host_field;
    $this->host_id = $host_id;
  }


}