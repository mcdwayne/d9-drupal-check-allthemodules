<?php

namespace Drupal\dream_fields\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dream_fields\FieldCreator;
use Drupal\dream_fields\FieldCreatorInterface;
use Drupal\field_ui\FieldUI;
use Drupal\field_ui\Form\FieldStorageAddForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigureField.
 *
 * @package Drupal\dream_fields\Form
 */
class ConfigureField extends FieldStorageAddForm {

  /**
   * The dream fields plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $dreamFieldsPluginManager;

  /**
   * The field creator.
   *
   * @var \Drupal\dream_fields\FieldCreatorInterface
   */
  protected $fieldCreator;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL, $field_type = NULL) {
    if (!$form_state->get('entity_type_id')) {
      $form_state->set('entity_type_id', $entity_type_id);
    }
    if (!$form_state->get('bundle')) {
      $form_state->set('bundle', $bundle);
    }
    if (!$form_state->get('plugin_id')) {
      $form_state->set('plugin_id', $field_type);
    }

    $instance = $this->dreamFieldsPluginManager->createInstance($field_type);

    $form['#title'] = $this->t('Configure @field', ['@field' => $instance->getPluginDefinition()['label']]);
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#required' => TRUE,
      '#weight' => -20,
    ];
    $form['required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Field is mandatory'),
      '#weight' => -5,
    ];
    if ($form_data = $instance->getForm()) {
      $form['plugin_configuration'] = $form_data;
    }
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Create field'),
        '#button_type' => 'primary',
      ],
    ];
    $form['#attached']['library'][] = 'dream_fields/dream-fields-form';
    $form['#tree'] = TRUE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getValue('label')) {
      $form_state->setErrorByName('label', $this->t('You need to provide a label.'));
    }
    $instance = $this->dreamFieldsPluginManager->createInstance($form_state->get('plugin_id'));
    $instance->validateForm($form_state->getValue('plugin_configuration', []), $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $instance = $this->dreamFieldsPluginManager->createInstance($form_state->get('plugin_id'));

    $bundle = $form_state->get('bundle');
    $entity_type_id = $form_state->get('entity_type_id');

    // Create a field builder with all the values we have access to, then give
    // it to the plugin to setup the specific fields required.
    $field_builder = FieldCreator::createBuilder()
      ->setEntityTypeId($entity_type_id)
      ->setBundle($bundle)
      ->setLabel($form_state->getValue('label'))
      ->setRequired($form_state->getValue('required'));

    $instance->saveForm($form_state->getValue('plugin_configuration', []), $field_builder);
    $this->fieldCreator->save($field_builder);
    $this->fieldUiRedirect($form_state, $entity_type_id, $bundle);
  }

  /**
   * Redirect to the field UI of an entity type.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   */
  protected function fieldUiRedirect(FormStateInterface $form_state, $entity_type_id, $bundle) {
    $entity_type = $this->entityManager->getDefinition($entity_type_id);
    $form_state->setRedirect("entity.$entity_type_id.field_ui_fields", FieldUI::getRouteBundleParameter($entity_type, $bundle));
    drupal_set_message('Your field is created and ready to be used, you can find more advanced options on this page or use the tabs at the top.');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dream_field_configure';
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityManagerInterface $entity_manager, FieldTypePluginManagerInterface $field_type_plugin_manager, QueryFactory $query_factory, ConfigFactoryInterface $config_factory, PluginManagerInterface $dream_fields_plugin_manager, FieldCreatorInterface $field_creator) {
    parent::__construct($entity_manager, $field_type_plugin_manager, $query_factory, $config_factory);
    $this->dreamFieldsPluginManager = $dream_fields_plugin_manager;
    $this->fieldCreator = $field_creator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('entity.query'),
      $container->get('config.factory'),
      $container->get('plugin.manager.dream_fields'),
      $container->get('dream_fields.field_creator')
    );
  }

}
