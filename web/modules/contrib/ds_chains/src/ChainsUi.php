<?php

namespace Drupal\ds_chains;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for altering the field UI.
 */
class ChainsUi implements ContainerInjectionInterface {

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * String translation.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translation;

  /**
   * Constructs a new ChainsUi object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   String translation.
   */
  public function __construct(EntityFieldManagerInterface $entityFieldManager, EntityTypeManagerInterface $entityTypeManager, TranslationInterface $translation) {
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->translation = $translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * Alter field UI manage display.
   *
   * @param array $form
   *   Form display.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function alterFieldUiManageDisplay(array &$form, FormStateInterface $form_state) {
    // Get the entity_type, bundle and view mode.
    $entity_type = $form['#entity_type'];
    $bundle = $form['#bundle'];

    /* @var \Drupal\Core\Entity\EntityFormInterface $entity_form */
    $entity_form = $form_state->getFormObject();
    /* @var \Drupal\Core\Entity\Display\EntityDisplayInterface $entity_display */
    $entity_display = $entity_form->getEntity();
    $view_mode = $entity_display->getMode();
    if (!$entity_display->getThirdPartySetting('ds', 'layout', FALSE)) {
      return;
    }
    $fields = $this->entityFieldManager->getFieldMapByFieldType('entity_reference');
    if (!isset($fields[$entity_type])) {
      return;
    }
    $options = [];
    $instances = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
    foreach ($fields[$entity_type] as $field_name => $details) {
      $field_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type);
      if (!isset($field_definitions[$field_name])) {
        // Calculated/computed field.
        continue;
      }
      $field_definition = $field_definitions[$field_name];
      $target_type = $field_definition->getSetting('target_type');
      $target_class = $this->entityTypeManager->getDefinition($target_type);
      if (!$target_class->entityClassImplements(ContentEntityInterface::class) || !$target_class->hasViewBuilderClass()) {
        continue;
      }
      if (isset($instances[$field_name])) {
        $options[$field_name] = $instances[$field_name]->getLabel();
      }
    }
    // Add chains form.
    $form['ds_chains'] = [
      '#type' => 'details',
      '#title' => t('Chained fields for @bundle in @view_mode', [
        '@bundle' => str_replace('_', ' ', $bundle),
        '@view_mode' => str_replace('_', ' ', $view_mode),
      ]),
      '#collapsible' => TRUE,
      '#group' => 'additional_settings',
      '#collapsed' => FALSE,
      '#weight' => -100,
      '#tree' => TRUE,
      'fields' => [
        '#type' => 'checkboxes',
        '#title' => $this->translation->translate('Enabled chained fields'),
        '#options' => $options,
        '#default_value' => $entity_display->getThirdPartySetting('ds_chains', 'fields', []),
      ],
    ];
    $form['#entity_builders'][] = [get_class($this), 'buildEntity'];
  }

  /**
   * Entity builder.
   */
  public static function buildEntity($entity_type, EntityViewDisplayInterface $display, array $form, FormStateInterface $form_state) {
    $display->setThirdPartySetting('ds_chains', 'fields', array_filter($form_state->getValue(['ds_chains', 'fields'], [])));
    \Drupal::service('plugin.manager.ds')->clearCachedDefinitions();
  }

}
