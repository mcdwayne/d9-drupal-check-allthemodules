<?php

namespace Drupal\pwned_passwords\Form;

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PwnedPasswordAdminForm.
 */
class PwnedPasswordAdminForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return [
      'pwned_passwords.config'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pwned_password_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $conf = $this->config('pwned_passwords.config');

    $form['forms'] = [
      '#type'        => 'details',
      '#title'       => $this->t("Forms"),
      '#description' => $this->t("Enable or disable the Pwned Password validation on forms"),
      '#open'        => TRUE,
      '#weight'      => '0',
    ];

    $enabled_forms = $conf->get('check_enabled_forms') ?: [];
    $form['forms']['check_enabled_forms'] = [
      '#type'          => 'checkboxes',
      '#title'         => $this->t('System forms'),
      '#description'   => $this->t('Select enabled System forms.'),
      '#options'       => $this->getFormsAsOptions(),
      '#default_value' => array_combine($enabled_forms, $enabled_forms),
    ];

    $form['widgets'] = [
      '#type'        => 'details',
      '#title'       => $this->t("Widgets"),
      '#description' => $this->t("Enable or disable the Pwned Password validation widgets"),
      '#weight'      => '1',
      '#open'        => TRUE,
    ];

    $form['widgets']['validate_all_passwords'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Validate all Password fields'),
      '#description'   => $this->t("Enable this option to validate <strong>all password fields</strong>, regardless of the form."),
      '#default_value' => $conf->get('validate_all_passwords'),
    ];

    $form['pwned_options'] = [
      '#type'        => 'details',
      '#title'       => $this->t("Options"),
      '#description' => $this->t("Customize based on your needs."),
      '#tree'        => TRUE,
      '#open'        => TRUE,
      '#weight'      => '2',
    ];

    $form['pwned_options']['threshold_warning'] = [
      '#type'          => 'number',
      '#title'         => $this->t("Warning-only Threshold"),
      '#description'   => $this->t("If grater than 0 and lower than <em>Error Threshold (see below)</em> the module will show a information message when the <em>Pwned count</em> is greater than what is set here."),
      '#default_value' => $conf->get('pwned_options.threshold_warning'),
      '#min'           => 0,
    ];

    $form['pwned_options']['error_blocks_submit'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Error blocks form submission'),
      '#description'   => $this->t("Enable this option prevents the form submission if the <em>Pwned count</em> is grater then <em>Error Threshold (see below)</em>."),
      '#default_value' => $conf->get('pwned_options.error_blocks_submit'),
    ];

    $form['pwned_options']['threshold_error'] = [
      '#type'          => 'number',
      '#title'         => $this->t("Error Threshold"),
      '#description'   => $this->t("If grather than 0 and lower than "),
      '#default_value' => $conf->get('pwned_options.threshold_error'),
      '#min'           => 0,
      '#states'        => [
        'visible' => [
          ':input[name="pwned_options[error_blocks_submit]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['messages'] = [
      '#type'        => 'details',
      '#title'       => $this->t("Messages"),
      '#description' => $this->t("Customize the mesages displayed to the user. You can use <code>@count</code> as placeholder for the match count."),
      '#tree'        => TRUE,
      '#open'        => TRUE,
      '#weight'      => '3',
    ];

    $form['messages']['warning'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t("Warning Message"),
      '#description'   => $this->t("This message will be shown when the password is accepted, but the match count is greater than the Warning Threshold."),
      '#default_value' => $conf->get('messages.warning'),
      '#rows'          => 2,
    ];

    $form['messages']['error'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t("Error Message"),
      '#description'   => $this->t("This message will be shown when the password is not accepted."),
      '#default_value' => $conf->get('messages.error'),
      '#rows'          => 2,
      '#states'        => [
        'visible' => [
          ':input[name="pwned_options[error_blocks_submit]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $conf = $this->config('pwned_passwords.config');

    $enabled_system_forms = array_filter($form_state->getValue('check_enabled_forms'));
    $conf->set('check_enabled_forms', $enabled_system_forms)
      ->set('validate_all_passwords', $form_state->getValue('validate_all_passwords'))
      ->set('pwned_options', $form_state->getValue('pwned_options'))
      ->set('messages', $form_state->getValue('messages'))
      ->save();

    /** @var \Drupal\Core\Render\ElementInfoManager $element_info_manager */
    $element_info_manager = \Drupal::service('plugin.manager.element_info');
    // Clear element cache to allow changing the registration of the option 'validate_all_passwords'
    $element_info_manager->clearCachedDefinitions();

    parent::submitForm($form, $form_state);
  }

  /**
   * Builds a list of forms for which the validation can be enabled.
   *
   * @see ModuleHandler::alter()
   *
   * @return array
   */
  public function getFormsAsOptions() {
    $available_forms = [
      'user_register_form' => $this->t('User registration form'),
      'user_form'          => $this->t('User edit form'),
      'user_login_form'    => $this->t('User login form <em>(Not recommended)</em>'),
    ];

    // TODO: Injection
    \Drupal::moduleHandler()->alter('pwned_check_form_options', $available_forms);
    if (!is_array($available_forms)) {
      throw new \RuntimeException("List of available forms must be a array of entries.");
    }

    return $available_forms;
  }
}
