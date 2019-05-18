<?php

namespace Drupal\production_checklist\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new SettingsForm object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
      EntityTypeManagerInterface $entity_type_manager
    ) {
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
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'production_checklist.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'production_checklist__settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('production_checklist.settings');
    $user = NULL;
    if ($config->get('notification_user') !== NULL) {
      $user = $this->entityTypeManager->getStorage('user')->load((int) $config->get('notification_user'));
    }
    drupal_set_message('The settings form is currently under development, notifications does not have any effect yet.', 'warning');
    $form['notification'] = [
      '#type' => 'fieldset',
      '#title' => t('Notification'),
      '#collapsible' => TRUE,
      '#description' => '',
    ];
    $form['notification']['notification_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable notifications'),
      '#description' => $this->t('Be warned once checked items have been invalidated by configuration.'),
      '#default_value' => $config->get('notification_enable'),
    ];
    $form['notification']['notification_user'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#title' => $this->t('User'),
      '#description' => $this->t('User that will receive the notifications.'),
      '#default_value' => $user,
      '#states' => [
        'invisible' => [
          ':input[name="notification_enable"]' => ['checked' => FALSE],
        ],
        'required' => [
          ':input[name="notification_enable"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['notification']['notification_preferences'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Notification preferences'),
      '#options' => ['on_site' => $this->t('On site'), 'email' => $this->t('Email')],
      '#default_value' => $config->get('notification_preferences'),
      '#states' => [
        'invisible' => [
          ':input[name="notification_enable"]' => ['checked' => FALSE],
        ],
        'required' => [
          ':input[name="notification_enable"]' => ['checked' => TRUE],
        ],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('production_checklist.settings')
      ->set('notification_enable', $form_state->getValue('notification_enable'))
      ->set('notification_user', $form_state->getValue('notification_user'))
      ->set('notification_preferences', $form_state->getValue('notification_preferences'))
      ->save();
  }

}
