<?php

namespace Drupal\elastic_search\Plugin\ElasticAbstractField;

/**
 * Interface ElasticAbstractFieldInterface
 */
interface ElasticAbstractFieldInterface {

  /**
   * @return array
   */
  public function getFieldTypes();

  /**
   * Gets the plugin weight.
   *
   * @return integer
   */
  public function getWeight();

  /**
   * Returns the plugin label.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function getLabel();

  /**
   * Returns the plugin description.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function getDescription();

  /**
   * Returns an array as it was coming from a fieldable entity.
   * E.g.
   * ['my_custom_field' => [
   *    ['map' => [
   *      [0 => [
   *        'type' => 'integer'
   *       ]
   *      ]
   *      'nested' => '',
   *     ]
   * ];
   *
   * @return array
   */
  public function getAbstractFields();

  /**
   * Returns a bool value if the field has subfields and should be treated as
   * nested value.
   *
   * This is useful if your field contains dynamic or static subfield e.g.
   * "min" to become field_yourfield.min.
   *
   * @return boolean
   */
  public function isNested();

}
