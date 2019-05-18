<?php

namespace Drupal\blockchain\Plugin\BlockchainData;

use Drupal\Core\Field\FieldFilteredMarkup;

/**
 * Class SerializableDataContainer.
 *
 * @package Drupal\blockchain\Utils
 */
class JsonDataContainer implements JsonBlockchainDataInterface {

  public $title;

  public $body;

  /**
   * Static constructor.
   */
  public static function create(array $values = []) {

    $object = new static();
    $object->fromArray($values);

    return $object;
  }

  /**
   * Constructor form json string.
   */
  public function fromJson($values) {

    if ($values = json_decode($values)) {
      foreach (get_object_vars($this) as $name => $value) {
        if (isset($values->{$name})) {
          $this->{$name} = $values->{$name};
        }
      }
    }
  }

  /**
   * Constructor form array.
   */
  public function fromArray(array $values) {

    if ($values) {
      foreach (get_object_vars($this) as $name => $value) {
        if (isset($values[$name])) {
          $this->{$name} = $values[$name];
        }
      }
    }
  }

  /**
   * Converts to json string.
   */
  public function toJson() {

    $values = [];
    foreach (get_object_vars($this) as $name => $value) {
      $values[$name] = $value;
    }

    return json_encode($values);
  }

  /**
   * Getter for widget render array.
   */
  public function getWidget() {

    $types = [
      'title' => 'textfield',
      'body' => 'textarea',
    ];
    $widget = [];
    foreach (get_object_vars($this) as $name => $value) {
      $type = $types[$name];
      $widget[$name] = [
        '#type' => $type,
        '#default_value' => $value,
        '#title' => t($this->humanize($name)),
        '#required' => TRUE,
      ];
    }

    return $widget;
  }

  /**
   * Getter for formatter render array.
   */
  public function getFormatter() {

    $markup = '';
    foreach (get_object_vars($this) as $name => $value) {
      $markup .= t($this->humanize($name)) . ':' . $value . '</br>';
    }

    return [
      '#type' => 'item',
      '#title' => t('Data'),
      '#markup' => $markup,
      '#allowed_tags' => FieldFilteredMarkup::allowedTags(),
    ];
  }

  /**
   * Converts to array.
   */
  public function toArray() {

    $array = [];
    foreach (get_object_vars($this) as $name => $value) {
      $array[$name] = $value;
    }

    return $array;
  }

  /**
   * Humanises string.
   */
  public function humanize($string) {

    return ucfirst(str_replace('_', ' ', $string));
  }

}
