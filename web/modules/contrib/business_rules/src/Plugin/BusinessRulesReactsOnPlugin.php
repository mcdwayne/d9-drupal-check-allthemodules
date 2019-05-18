<?php

namespace Drupal\business_rules\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for Business rules reacts on plugins.
 */
abstract class BusinessRulesReactsOnPlugin extends PluginBase implements BusinessRulesReactsOnInterface {

  /**
   * The business rules processor.
   *
   * @var \Drupal\business_rules\Util\BusinessRulesProcessor
   */
  protected $processor;

  /**
   * The business rules util.
   *
   * @var \Drupal\business_rules\Util\BusinessRulesUtil
   */
  protected $util;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->processor = \Drupal::service('business_rules.processor');
    $this->util      = \Drupal::service('business_rules.util');
  }

  /**
   * {@inheritdoc}
   */
  public function processForm(array &$form, FormStateInterface $form_state) {

  }

}
