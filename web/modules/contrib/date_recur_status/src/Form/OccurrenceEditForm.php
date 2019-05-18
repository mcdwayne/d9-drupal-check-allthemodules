<?php

namespace Drupal\date_recur_status\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

/**
 * Class OccurrenceEditForm.
 *
 * @package Drupal\date_recur_status\Form
 */
class OccurrenceEditForm extends FormBase {

  /**
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;


  /**
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagInvalidator;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;


  public function __construct(
    ConfigFactoryInterface $configFactory,
    Connection $database,
    DateFormatterInterface $dateFormatter,
    CacheTagsInvalidatorInterface $cacheTagInvalidator,
    ModuleHandlerInterface $moduleHandler
  ) {
    $this->config = $configFactory;
    $this->database = $database;
    $this->dateFormatter = $dateFormatter;
    $this->cacheTagInvalidator = $cacheTagInvalidator;
    $this->moduleHandler = $moduleHandler;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('database'),
      $container->get('date.formatter'),
      $container->get('cache_tags.invalidator'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'occurrence_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    $fields = [];
    foreach ($node->getFieldDefinitions() as $field) {
      if ($field->getType() == 'date_recur' && $field->getSetting('occurrence_handler_plugin') == 'date_recur_status_occurrence_handler') {
        $fields[] = $field->getName();
      }
    }

    $form['fields'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];
    foreach ($fields as $field) {
      $form['fields'][$field] = [
        '#type' => 'fieldset',
        '#title' => $node->getFieldDefinition($field)->label(),
      ];
      $items = $node->get($field);
      /** @var DateRecurItem $item */
      foreach ($items as $delta => $item) {
        $occurrences = $item->getOccurrenceHandler()->getOccurrencesForDisplay();
        foreach ($occurrences as $i => $occurrence) {
          $form['fields'][$field][$occurrence['field_delta'] . '/' . $occurrence['delta']] = [
            '#type' => 'select',
            '#title' => $this->dateFormatter->format($occurrence['value']->getTimestamp()),
            '#options' => $this->getStatusList(),
            '#default_value' => $occurrence['status'],
          ];
        }
      }
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];

    $form['#node'] = $node;

    return $form;
  }

  public function getStatusList() {
    return $this->config->get('date_recur_status.settings')->get('statuses');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, $node = NULL) {
    /** @var Node $node */
    $node = $form['#node'];
    // Display result.
    $field_values = $form_state->getValue('fields');
    $change = 0;
    foreach ($field_values as $field => $values) {
      $handler = $node->get($field)->get(0)->getOccurrenceHandler();
      foreach ($values as $key => $value) {
        $element = $form['fields'][$field][$key];
        $default = $element['#default_value'];
        list($field_delta, $delta) = explode('/', $key);
        if ($default != $value) {
          $handler->updateStatusField($field_delta, $delta, $value);
          $change++;
        }
      }
    }
    drupal_set_message(t('The occurrence status changed were saved.', ['@count' => $change]));
    // Invalid caches.
    $this->cacheTagInvalidator->invalidateTags([
      'node:' . $node->id()
    ]);
    // Trigger re-index.
    if ($this->moduleHandler->moduleExists('search_api')) {
      $this->trackSearchApiUpdate($node);
    }
  }

  /**
   * Trigger search api index update.
   *
   * @see search_api_entity_update();
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   */
  protected function trackSearchApiUpdate(ContentEntityInterface $entity) {
    $indexes = \Drupal\search_api\Plugin\search_api\datasource\ContentEntity
::getIndexesForEntity($entity);
    $datasource_id = 'entity:' . $entity->getEntityTypeId();
    if (!$indexes) {
      return;
    }
    $entity_id = $entity->id();
    $updated_item_ids = $entity->getTranslationLanguages();
    $combine_id = function ($langcode) use ($entity_id) {
      return $entity_id . ':' . $langcode;
    };
    $updated_item_ids = array_map($combine_id, array_keys($updated_item_ids));
    foreach ($indexes as $index) {
      $index->trackItemsUpdated($datasource_id, $updated_item_ids);
    }
  }

  public function accessFormPage(RouteMatchInterface $route_match, AccountInterface $account, Node $node) {
    /** @var Node $node */
    if (empty($node)) {
      return AccessResult::forbidden();
    }
    if (!$account->hasPermission('edit occurrence status')) {
      return AccessResult::forbidden();
    }
    foreach ($node->getFieldDefinitions() as $field) {
      if ($field->getType() == 'date_recur') {
        return AccessResult::allowed();
      }
    }
    return AccessResult::forbidden();
  }
}
