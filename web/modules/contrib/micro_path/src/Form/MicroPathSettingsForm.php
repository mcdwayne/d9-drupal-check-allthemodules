<?php

namespace Drupal\micro_path\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MicroPathSettingsForm.
 *
 * @package Drupal\micro_path\Form
 * @ingroup micro_path
 */
class MicroPathSettingsForm extends ConfigFormBase {

  /**
   * Alias schema max length.
   *
   * @var int
   */
  protected $aliasSchemaMaxLength = 255;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'micro_path_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['micro_path.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('micro_path.settings');
    $enabled_entity_types = $config->get('entity_types');

    $form['entity_types'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Enabled entity types for micro paths'),
      '#tree' => TRUE,
    ];

    // Get all applicable entity types.
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if (is_subclass_of($entity_type->getClass(), FieldableEntityInterface::class) && $entity_type->hasLinkTemplate('canonical')) {
        $default_value = !empty($enabled_entity_types[$entity_type_id]) ? $enabled_entity_types[$entity_type_id] : NULL;
        $form['entity_types'][$entity_type_id] = [
          '#type' => 'checkbox',
          '#title' => $entity_type->getLabel(),
          '#default_value' => $default_value,
        ];
      }
    }

    $form['max_length'] = array(
      '#type' => 'number',
      '#title' => $this->t('Maximum alias length'),
      '#size' => 3,
      '#maxlength' => 3,
      '#default_value' => $config->get('max_length'),
      '#min' => 1,
      '#max' => $this->aliasSchemaMaxLength,
      '#description' => $this->t('Maximum length of aliases to generate. 100 is the recommended length. @max is the maximum possible length.', array('@max' => $this->aliasSchemaMaxLength)),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('micro_path.settings')
      ->set('entity_types', $form_state->getValue('entity_types'))
      ->set('max_length', $form_state->getValue('max_length'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
