<?php

namespace Drupal\empty_fields;

use Drupal\Core\Plugin\PluginBase;

/**
 * Defines a base empty field item implementation.
 *
 * @see \Drupal\empty_fields\Annotation\EmptyField
 * @see \Drupal\empty_fields\EmptyFieldPluginInterface
 * @see \Drupal\empty_fields\EmptyFieldsPluginManager
 * @see plugin_api
 */
abstract class EmptyFieldPluginBase extends PluginBase implements EmptyFieldPluginInterface {

  /**
   * The label which is used for render of this plugin.
   *
   * @var string
   */
  protected $label;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->get('id');
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->get('title');
  }

  /**
   * {@inheritdoc}
   */
  public function defaults() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function form($context) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  abstract public function summaryText();

  /**
   * {@inheritdoc}
   */
  abstract public function react($content);

}
