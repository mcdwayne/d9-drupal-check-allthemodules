<?php

namespace Drupal\smart_content_segments\Plugin\smart_content\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\smart_content\Condition\ConditionBase;
use Drupal\smart_content\Condition\ConditionConfigurableBase;
use Drupal\smart_content_segments\Entity\SmartSegment;

/**
 * Provides a Smart Segment condition plugin.
 *
 * @SmartCondition(
 *   id = "smart_segment",
 *   label = @Translation("Smart Segment"),
 *   group = "segment",
 *   deriver = "Drupal\smart_content_segments\Plugin\Derivative\SegmentDeriver"
 * )
 */
class Segment extends ConditionConfigurableBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();

    $form = ConditionBase::attachNegateElement($form, $configuration);
    $condition_definition = $this->getPluginDefinition();
    $form['label'] = [
      '#type' => 'container',
      '#markup' => $condition_definition['label'] . '(' . $condition_definition['group'] . ')',
      '#attributes' => [
        'class' => ['condition-label'],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $configuration = $form_state->getValues();
    $form_title = $form['#title'];
    $configuration['segment_id'] = explode(':', $form_title)[1];
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function writeChangesToConfiguration() {
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries() {
    $libraries = ['smart_content_segments/condition.smart_segment'];
    foreach ($this->getConditions() as $condition) {
      $libraries = array_merge($libraries, $condition->getLibraries());
    }
    return $libraries;
  }

  /**
   * {@inheritdoc}
   */
  function getClientSideEvaluateLibraries() {
    $libraries = ['smart_content/condition_type.standard'];
    foreach ($this->getConditions() as $condition) {
      $libraries = array_merge($libraries, $condition->getLibraries());
    }
    return array_unique($libraries);
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachedSettings($processed_client = FALSE) {
    $definition = $this->getPluginDefinition();
    $condition_settings = [];
    foreach ($this->getConditions() as $condition) {
      $condition_settings[] = $condition->getAttachedSettings();
    }
    return [
      'field' => [
        'pluginId' => $this->getPluginId(),
        'unique' => $definition['unique'],
        'conditions' => $condition_settings,
      ],
      'settings' => [
        'negate' => $this->getConfiguration()['negate'],
      ],
    ];
  }

  /**
   * Get the conditions that are a part of this segment.
   *
   * @return array
   */
  protected function getConditions() {
    $segment_id = $this->getConfiguration()['segment_id'];
    if ($entity = SmartSegment::load($segment_id)) {
      return $entity->getConditions();
    }
    return [];
  }

}
