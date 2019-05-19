<?php

namespace Drupal\workflow_participants\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Workflow participants settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs the form.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['workflow_participants.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workflow_participants_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('workflow_participants.settings');

    $form['enable_notifications'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable notifications for new participants'),
      '#description' => $this->t('If enabled, newly added workflow participants will be notified with the email configured below.'),
      '#default_value' => $config->get('enable_notifications'),
    ];

    $form['participant_message'] = [
      '#type' => 'details',
      '#tree' => TRUE,
      '#title' => $this->t('Notification email'),
      '#states' => [
        'visible' => [
          ':input[name = "enable_notifications"]' => ['checked' => TRUE],
        ],
      ],
      '#open' => TRUE,
    ];

    $form['participant_message']['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#states' => [
        'required' => [
          ':input[name = "enable_notifications"]' => ['checked' => TRUE],
        ],
      ],
      '#default_value' => $config->get('participant_message.subject'),
    ];

    $form['participant_message']['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Email body'),
      '#states' => [
        'required' => [
          ':input[name = "enable_notifications"]' => ['checked' => TRUE],
        ],
      ],
      '#default_value' => $config->get('participant_message.body.value'),
      '#format' => $config->get('participant_message.body.format'),
    ];

    if ($this->moduleHandler->moduleExists('token')) {
      $form['participant_message']['token_tree'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => 'all',
        '#show_restricted' => TRUE,
        '#theme_wrappers' => ['form_element'],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // If notifications are enabled, require message body and subject.
    if ($form_state->getValue('enable_notifications')) {
      if (!$form_state->getValue(['participant_message', 'subject'])) {
        $form_state->setErrorByName('participant_message][subject', $this->t('Email subject is required.'));
      }
      if (!$form_state->getValue(['participant_message', 'body', 'value'])) {
        $form_state->setErrorByName('participant_message][body][value', $this->t('Email body is required.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('workflow_participants.settings');

    foreach (['enable_notifications', 'participant_message'] as $key) {
      $config->set($key, $form_state->getValue($key));
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
