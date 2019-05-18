<?php

namespace Drupal\blackfire\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for Blackfire.
 */
class BlackfireSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  static public function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * BlackfireSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'blackfire_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['blackfire.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('blackfire.settings');

    $entity_types = [];
    foreach ($this->entityTypeManager->getDefinitions() as $type) {
      $entity_types[$type->id()] = $type->getLabel();
    }
    asort($entity_types);

    $uncached = [
      '#type' => 'checkboxes',
      '#options' => $entity_types,
      '#default_value' => $config->get('uncached'),
    ];

    $form['uncached'] = [
      '#type' => 'details',
      '#title' => $this->t('Profile uncached behavior'),
      '#description' => $this->t('For each selected entity type, a small edit will be simulated when profiling.'),
      '#open' => !empty($config->get('uncached')),
      'uncached' => $uncached,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('blackfire.settings')
      ->set('uncached', array_filter($form_state->getValue('uncached')))
      ->save();
  }

}
