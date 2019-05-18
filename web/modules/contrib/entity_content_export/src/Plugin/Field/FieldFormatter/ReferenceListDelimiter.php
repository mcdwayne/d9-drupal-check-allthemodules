<?php

namespace Drupal\entity_content_export\Plugin\Field\FieldFormatter;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define list delimiter.
 *
 * @FieldFormatter(
 *   id = "entity_content_export_reference_list_delimiter",
 *   label = @Translation("List delimiter"),
 *   field_types = {"entity_reference"}
 * )
 */
class ReferenceListDelimiter extends ListDelimiterFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Reference list delimiter constructor.
   *
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   * @param array $settings
   * @param $label
   * @param $view_mode
   * @param array $third_party_settings
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    EntityFieldManagerInterface $entity_field_manager
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $label,
      $view_mode,
      $third_party_settings
    );
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'field_name' => NULL,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $field_name = $this->getSetting('field_name');

    if (!isset($field_name)) {
      $field_name = $this->t('None');
    }
    $summary[] = $this->t('<strong>Field name</strong>: @field_name', [
      '@field_name' => $field_name
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $settings = $this->getSettings();

    $form['field_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Field'),
      '#description' => $this->t('Select the field to retrieve the value from.'),
      '#required' => TRUE,
      '#options' => $this->getReferencedFieldOptions(),
      '#default_value' => $settings['field_name'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getFieldItemArrayList(FieldItemListInterface $items) {
    $list = [];

    if (!$items->isEmpty()) {
      $field_name = $this->getSetting('field_name');

      /** @var \Drupal\Core\Field\FieldItemBase $item */
      foreach ($items as $item) {
        if ($item->isEmpty() || !$item instanceof EntityReferenceItem) {
          continue;
        }
        /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
        $entity = $item->entity;
        $value = $item->getString();

        if (isset($field_name)) {
          /** @var FieldItemListInterface $field_item */
          $field_item = $entity->{$field_name};

          if (!isset($field_item) || $field_item->isEmpty()) {
            continue;
          }
          $value = $field_item->getString();
        }

        $list[] = $value;
      }
    }

    return $list;
  }

  /**
   * Get field definition.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The field definitions.
   */
  protected function getFieldDefinition() {
    return $this->fieldDefinition;
  }

  /**
   * Get referenced entity type.
   *
   * @return mixed
   *   An array of base fields definitions for referenced entity.
   */
  protected function getReferencedEntityType() {
    return $this->getFieldDefinition()->getSetting('target_type');
  }

  /**
   * Get referenced based field definitions.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   */
  protected function getReferencedBaseFieldDefinitions() {
    return $this
      ->entityFieldManager
      ->getBaseFieldDefinitions($this->getReferencedEntityType());
  }

  /**
   * Get referenced field options.
   *
   * @return array
   *   An array of referenced field options.
   */
  protected function getReferencedFieldOptions() {
    $options = [];

    /** @var \Drupal\Core\Field\BaseFieldDefinition $definition */
    foreach ($this->getReferencedBaseFieldDefinitions() as $field_name => $definition) {
      if ($definition->isComputed()) {
        continue;
      }
      $options[$field_name] = $definition->getLabel();
    }

    return $options;
  }
}
