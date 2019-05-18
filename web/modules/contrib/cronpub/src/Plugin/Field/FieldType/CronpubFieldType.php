<?php

/**
 * @file
 * Contains \Drupal\cronpub\Plugin\Field\FieldType\CronpubFieldType.
 */

namespace Drupal\cronpub\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\cronpub\Plugin\Cronpub\CronpubActionManager;


/**
 * Plugin implementation of the 'cronpub_field_type' field type.
 *
 * @FieldType(
 *   id = "cronpub_field_type",
 *   label = @Translation("Cronpub date field"),
 *   description = @Translation("Enter the date/s and rules for handling (for example un-/publish) the content entity."),
 *   default_widget = "cronpub_subform_widget",
 *   default_formatter = "cronpub_default"
 * )
 */
class CronpubFieldType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    $settings = [
        'plugin' => 'publishing',
      ] + parent::defaultStorageSettings();

    return $settings;
  }

  /**
   * @var \Drupal\cronpub\Plugin\Cronpub\CronpubActionManager
   */
  private $plugin_manager;

  /**
   *
   */
  private $cronpub_entity;

  /**
   * Get the plugin manager for Cronpub plugins.
   * @return \Drupal\cronpub\Plugin\Cronpub\CronpubActionManager
   */
  public function getPluginManager() {
    if (!$this->plugin_manager instanceof CronpubActionManager) {
      $this->plugin_manager = \Drupal::service('plugin.manager.cronpub');
    }
    return $this->plugin_manager;
  }

  /**
   *
   */
  public function getCronpubEntity() {
    if(!$this->cronpub_entity) {
      $this->setCronpubEntity();
    }
    return $this->cronpub_entity;
  }


  /**
   *
   */
  private function setCronpubEntity() {
    /* @var $entity \Drupal\Core\Entity\EntityInterface */
    $entity = $this->getEntity();
    $params = [
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
      'plugin' => $this->getSetting('plugin'),
      'field_name' => $this->getParent()->getName(),
    ];

    $cronpub = \Drupal::entityTypeManager()
      ->getStorage('cronpub_entity')->loadByProperties($params);
    $this->cronpub_entity = reset($cronpub) ?: NULL;
  }


  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['start'] = DataDefinition::create('any')
      ->setLabel(new TranslatableMarkup('Date value'));
    $properties['date_start'] = DataDefinition::create('any')
      ->setLabel(new TranslatableMarkup('Computed date'))
      ->setDescription(new TranslatableMarkup('The computed DateTime object.'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\datetime\DateTimeComputed')
      ->setSetting('date source', 'start');

    $properties['end'] = DataDefinition::create('any')
      ->setLabel(new TranslatableMarkup('Date value'));
    $properties['date_end'] = DataDefinition::create('any')
      ->setLabel(new TranslatableMarkup('Computed date'))
      ->setDescription(new TranslatableMarkup('The computed DateTime object.'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\datetime\DateTimeComputed')
      ->setSetting('date source', 'end');
    // Lis of RRULE properties.
    $properties['rrule'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('RRule string'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'start' => [
          'type' => 'varchar',
          'length' => 128,
        ],
        'end' => [
          'type' => 'varchar',
          'length' => 128,
        ],
        'rrule' => [
          'type' => 'varchar',
          'length' => 256,
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $value = $this->getValue();
    if (!empty($value['rrule']) && ($value['end'] == NULL || $value['start'] == NULL)) {
      // CronpubIcalService service affords start AND end date.
      $constraints[] = $constraint_manager->create('ComplexData', [
        'end' => [
          'NotNull' => [
            'message' => $this->t('If a recurrence rule is defined, end date has also to be defined.'),
          ],
        ],
        'start' => [
          'NotNull' => [
            'message' => $this->t('If a recurrence rule is defined, start date has also to be defined.'),
          ],
        ],
      ]);
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values['start'] = time() + 180;
    $values['end']   = $values['start'] + 180;
    $values['rrule'] = 'FREQ=DAILY;COUNT=7';
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = [];
    $plugins = $this->getPluginManager()->getDefinitions();
    $options = [];
    foreach ($plugins as $plugin) {
      $options[$plugin['id']] = (string) $plugin['label'];
    }
    $elements['plugin'] = [
      '#type' => 'select',
      '#title' => $this->t('Plugin to use'),
      '#description' => $this->t('Select an action-plugin defining what to do at the start and at the end date.'),
      '#default_value' => $this->getSetting('plugin'),
      '#multiple' => FALSE,
      '#options' => $options,
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $start = (string) $this->get('start')->getValue();
    $end = (string) $this->get('end')->getValue();
    $rrule = (string) $this->get('rrule')->getValue();
    $empty = ($start == "") && ($end == "") && ($rrule == "");
    return $empty;
  }

}
