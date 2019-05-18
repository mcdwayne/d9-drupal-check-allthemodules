<?php

namespace Drupal\better_messages\Plugin\Condition;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'Message type' condition.
 *
 * @Condition(
 *   id = "message_type",
 *   label = @Translation("Message type"),
 *   context = {
 *     "better_messages" = @ContextDefinition("map",
 *       label = @Translation("Current better messages")
 *     )
 *   }
 * )
 */
class MessageType extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['message_types' => []] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['message_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Message types'),
      '#default_value' => $this->configuration['message_types'],
      '#options' => $this->messageTypeOptions(),
      '#description' => $this->t('Specify for which message types to active the condition. Leaving empty means for all message types.'),
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['message_types'] = array_values(array_filter($form_state->getValue('message_types')));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $message_labels = array_intersect_key($this->messageTypeOptions(), array_combine($this->configuration['message_types'], $this->configuration['message_types']));
    return implode(', ', $message_labels);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $messages = $this->getContextValue('better_messages');
    $intersect = array_intersect($this->configuration['message_types'], array_keys($messages));
    return empty($this->configuration['message_types']) || !empty($intersect);
  }

  /**
   * Retrieve a list of known message types.
   *
   * @return array
   *   Array of known message types. Keys are their machine names whereas values
   *   are the corresponding human friendly labels
   */
  protected function messageTypeOptions() {
    return [
      'status' => $this->t('Status'),
      'warning' => $this->t('Warning'),
      'error' => $this->t('Error'),
    ];
  }

}
