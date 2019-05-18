<?php

namespace Drupal\path_file\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PathFileEntitySettingsForm.
 *
 * @package Drupal\path_file\Form
 *
 * @ingroup path_file
 */
class PathFileEntitySettingsForm extends ConfigFormBase {

  /**
   * Entity Definition Update Manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $entityDefinitionUpdateManager
   *   Entity definition update manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityDefinitionUpdateManagerInterface $entityDefinitionUpdateManager) {
    $this->setConfigFactory($config_factory);

    $this->entityDefinitionUpdateManager = $entityDefinitionUpdateManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    $container->get('config.factory'),
    $container->get('entity.definition_update_manager')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'PathFileEntity_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'path_file.settings',
    ];
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $allowed_extensions = $form_state->getValue('allowed_extensions');

    $config = $this->config('path_file.settings');
    $config->set('allowed_extensions', $allowed_extensions)->save();

    // @TODO - is there a cleaner way to rebuild the field definition
    $field = $this->entityDefinitionUpdateManager->getFieldStorageDefinition("fid", "path_file_entity");
    $this->entityDefinitionUpdateManager->updateFieldStorageDefinition($field);

    parent::submitForm($form, $form_state);
  }

  /**
   * Defines the settings form for Path file entity entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['PathFileEntity_settings']['#markup'] = 'Settings form for Path file entity entities. Manage field settings here.';

    $config = $this->config('path_file.settings');
    $allowed_extensions = $config->get('allowed_extensions');

    $form['allowed_extensions'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Allowed File Extensions'),
      '#default_value' => $allowed_extensions,
    );

    return parent::buildForm($form, $form_state);
  }

}
