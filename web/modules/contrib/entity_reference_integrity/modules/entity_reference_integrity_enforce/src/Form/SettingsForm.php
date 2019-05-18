<?php

namespace Drupal\entity_reference_integrity_enforce\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The settings form for entity_reference_integrity.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * An array of entity type definitions.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface[]
   */
  protected $entityTypeDefinitions;

  /**
   * Create a SettingsForm.
   */
  public function __construct(ConfigFactoryInterface $config_factory, $entity_type_definitions) {
    parent::__construct($config_factory);
    $this->entityTypeDefinitions = $entity_type_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')->getDefinitions()
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['entity_reference_integrity_enforce.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_reference_integrity_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = [];
    foreach ($this->entityTypeDefinitions as $definition) {
      $options[$definition->id()] = $definition->getLabel() ?: $definition->id();
    }
    uasort($options, 'strcasecmp');
    $form['intro'] = [
      '#prefix' => '<p>',
      '#markup' => $this->t('Select the entity types which, when referenced will be prevented from being deleted.'),
      '#suffix' => '</p>',
    ];
    $form['enabled_entity_type_ids'] = [
      '#title' => $this->t('Enabled Entities'),
      '#type' => 'checkboxes',
      '#multiple' => TRUE,
      '#options' => $options,
      '#default_value' => $this->config('entity_reference_integrity_enforce.settings')->get('enabled_entity_type_ids'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this
      ->config('entity_reference_integrity_enforce.settings')
      ->set('enabled_entity_type_ids', $form_state->getValue('enabled_entity_type_ids'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
