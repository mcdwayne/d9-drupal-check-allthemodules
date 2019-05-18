<?php

namespace Drupal\development_environment\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * DevelopmentEnvironmentConfigurationForm class.
 *
 * Form definition for the Development Environment Configuration Form.
 */
class DevelopmentEnvironmentSettingsForm extends FormBase {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Instance specific settings.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * Constructs a DevelopmentEnvironmentSettingsForm object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Site\Settings $settings
   *   Instance specific settings.
   */
  public function __construct(StateInterface $state, Settings $settings) {
    $this->state = $state;
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('settings')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'development_environment_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Retrieve a value from settings.php, if one has been set.
    $settings_value = $this->settings->get('development_environment.log_emails');
    if (is_null($settings_value)) {
      $form['log_emails'] = [
        '#title' => $this->t('Log Emails'),
        '#type' => 'checkbox',
        '#default_value' => $this->state->get('development_environment.log_emails'),
        '#description' => $this->t('If this box is checked, emails will not longer be sent from this system, and will instead be written to a log. Note that this setting is NOT configuration, and will not migrate between environments. This value needs to be set independently for each environment.'),
      ];

      $form['log_emails_gui_description'] = [
        '#prefix' => '<p>',
        '#suffix' => '</p>',
        '#markup' => $this->t("To set this value and prevent it from being able to be managed through the admin UI, add a line containing <code>&#36;settings[\'development_environment.log_emails\'] = TRUE;</code> (or FALSE) to settings.php."),
      ];
    }
    else {
      $form['log_emails'] = [
        '#type' => 'value',
        '#value' => $settings_value,
      ];

      $form['log_emails_display'] = [
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];

      if ($settings_value) {
        $form['log_emails_display']['#markup'] = $this->t("Emails on the system are set to be logged in settings.php, and will NOT be sent from the system. To set emails to be sent rather than logged, edit settings.php, and set the value of <code>&#36;settings[\'development_environment.log_emails\']</code> to FALSE.");
      }
      else {
        $form['log_emails_display']['#markup'] = $this->t("Emails on the system are NOT set to be logged in settings.php, and will be sent from the system. To set emails to be logged rather than sent, edit settings.php, and set the value of <code>&#36;settings[\'development_environment.log_emails\']</code> to TRUE.");
      }

      $form['log_emails_gui_description'] = [
        '#prefix' => '<p>',
        '#suffix' => '</p>',
        '#markup' => $this->t("To manage this setting through the admin UI, remove the line containing <code>&#36;settings[\'development_environment.log_emails\']</code> from settings.php altogether."),
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->state->set('development_environment.log_emails', $form_state->getValue('log_emails'));
    drupal_set_message('The settings have been updated');
  }

}
