<?php

namespace Drupal\elastic_search\Plugin\ElasticAbstractField;

use Drupal\Core\Plugin\PluginBase;

/**
 * Class ElasticAbstractFieldBase
 *
 * @package Drupal\elastic_search\Plugin\ElasticAbstractField
 */
abstract class ElasticAbstractFieldBase extends PluginBase implements ElasticAbstractFieldInterface {

  /**
   * {@inheritdoc}
   */
  public function getFieldTypes() {
    return $this->pluginDefinition['field_types'];
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->pluginDefinition['weight'];
  }


  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }


  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

}
