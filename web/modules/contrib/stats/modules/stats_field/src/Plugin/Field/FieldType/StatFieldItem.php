<?php

namespace Drupal\stats_field\Plugin\Field\FieldType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\options\Plugin\Field\FieldType\ListStringItem;

/**
 * Plugin implementation of the 'stat' field type.
 *
 * @FieldType(
 *   id = "stat",
 *   label = @Translation("Stat field item"),
 *   description = @Translation("Provides field for reacting to stat selection"),
 *   category = @Translation("Reference"),
 *   default_widget = "options_select",
 *   default_formatter = "list_default",
 * )
 */
class StatFieldItem extends ListStringItem {

  /**
   * @var \Drupal\stats\StatsExecutor
   */
  protected $statsExecutor;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Drupal\Core\TypedData\DataDefinitionInterface $definition, $name = NULL, \Drupal\Core\TypedData\TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    // @todo: dependency injection.
    $this->statsExecutor = \Drupal::service('stats.executor');
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    // We do not use options_allowed_values() as this does not allow different
    // values for different bundles.
    $entity = $this->getEntity();

    $processors = $this->statsExecutor->getStatProcessorsForEntity($entity);
    $options = [];
    foreach ($processors as $processor) {
      $options[$processor->id()] = (string) $processor->label();
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'execute_on' => [],
    ]  + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $execute_on = $this->getSetting('execute_on');

    $element['execute_on'] = [
      '#type' => 'checkboxes',
      '#title' => t('Execute on'),
      '#default_value' => $execute_on,
      '#description' => t('The selected stat sets will be queued for execution on the given occasions.'),
      '#options' => [
        'insert' => t('Entity insert'),
        'update' => t('Entity update'),
        'delete' => t('Entity delete'),
      ]
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    $execute_on = $this->getSetting('execute_on');
    if (empty($execute_on[$update ? 'update' : 'insert'])) {
      return;
    }

    /** @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = \Drupal::service('queue')->get('stat_executor');
    // @todo move to itemList, so we can handle order of execution.
    $entity = $this->getEntity();
    $data = [
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
      'value' => $this->getValue()['value'],
    ];
    $queue->createItem($data);
  }
}
