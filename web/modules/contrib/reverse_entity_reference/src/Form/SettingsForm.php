<?php

namespace Drupal\reverse_entity_reference\Form;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form.
 *
 * @package Drupal\reverse_entity_reference\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Field Type Manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, FieldTypePluginManagerInterface $fieldTypeManager, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($config_factory);
    $this->fieldTypeManager = $fieldTypeManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reverse_entity_reference_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default / Previous settings.
    // @TODO: hide api key / client id values ... should be password field.
    $config = $this->config('reverse_entity_reference.settings');
    $field_types = $this->getReferenceFieldTypes();
    $entity_types = $this->getEntityTypes();

    $form['allowed_field_types'] = [
      '#type' => 'select',
      '#title' => $this->t('Allowed Field Types'),
      '#default_value' => $config->get('allowed_field_types'),
      '#options' => $field_types,
      '#multiple' => TRUE,
    ];

    $form['disallowed_entity_types'] = [
      '#type' => 'select',
      '#title' => $this->t('Disallowed Entity Types'),
      '#description' => $this->t('A list of entity types that you do not want to be reverse referenced.'),
      '#default_value' => $config->get('disallowed_entity_types'),
      '#options' => $entity_types,
      '#multiple' => TRUE,
    ];

    $form['allow_custom_storage'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow Custom Storage'),
      '#description' => $this->t("Use with caution with disallowed entity types. Often times, an entity that has custom storage shouldn't be reverse referenced because they can't be queried from the database."),
      '#default_value' => $config->get('allow_custom_storage'),
    ];
    return $form;
  }

  /**
   * Field types getter.
   *
   * Gets any field types that extend from the entity_reference field.
   * i.e. entity_reference_revisions, file, image etc.
   *
   * @return string[]
   *   an array of field names field by field type id.
   */
  protected function getReferenceFieldTypes() {
    $field_definitions = $this->fieldTypeManager->getDefinitions();
    $field_definitions = array_filter($field_definitions, function ($definition) use ($field_definitions) {
      $er_extension = is_subclass_of($definition['class'], $field_definitions['entity_reference']['class']);
      $is_er = $definition['class'] === $field_definitions['entity_reference']['class'];
      return ($er_extension || $is_er);
    });
    return array_combine(array_keys($field_definitions), array_map(function ($definition) {
      return $definition['label'];
    }, $field_definitions));
  }

  /**
   * Gets the entity type list suitable for a select list.
   *
   * @return string[]
   *   an array of entity type names by entity type id.
   */
  protected function getEntityTypes() {
    $entity_types = $this->entityTypeManager->getDefinitions();

    return array_combine(array_keys($entity_types), array_map(function (EntityTypeInterface $definition) {
      return $definition->getLabel();
    }, $entity_types));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('reverse_entity_reference.settings')
      ->set('allowed_field_types', $form_state->getValue('allowed_field_types'))
      ->set('disallowed_entity_types', $form_state->getValue('disallowed_entity_types'))
      ->set('allow_custom_storage', $form_state->getValue('allow_custom_storage'))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'reverse_entity_reference.settings',
    ];
  }

}
