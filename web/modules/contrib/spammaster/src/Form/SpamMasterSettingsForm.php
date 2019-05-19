<?php

namespace Drupal\spammaster\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\spammaster\Controller\SpamMasterLicController;

/**
 * Class controller.
 */
class SpamMasterSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'spammaster_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Form constructor.
    $form = parent::buildForm($form, $form_state);

    // Default settings.
    $config = $this->config('spammaster.settings');
    // Type.
    $response_key = $config->get('spammaster.license_status');
    // Statuses  Settings.
    $response_type = $config->get('spammaster.type');
    $spammaster_protection_total_number = $config->get('spammaster.license_protection');
    // STATUS VALID.
    if ($response_key == 'VALID') {
      $license_status = "VALID LICENSE";
      $protection_total_number_text = number_format($spammaster_protection_total_number) . ' Threats & Exploits';
    }
    // STATUS EXPIRED.
    if ($response_key == 'EXPIRED') {
      $license_status = "EXPIRED LICENSE";
      $protection_total_number_text = "0 Threats & Exploits - EXPIRED OFFLINE";
    }
    // STATUS MALFUNCTION_1.
    if ($response_key == 'MALFUNCTION_1') {
      $license_status = "VALID LICENSE";
      $protection_total_number_text = number_format($spammaster_protection_total_number) . ' Threats & Exploits';
    }
    // STATUS MALFUNCTION_2.
    if ($response_key == 'MALFUNCTION_2') {
      $license_status = "VALID LICENSE";
      $protection_total_number_text = number_format($spammaster_protection_total_number) . ' Threats & Exploits';
    }
    // STATUS MALFUNCTION_3.
    if ($response_key == 'MALFUNCTION_3') {
      $license_status = "MALFUNCTION_3 OFFLINE";
      $protection_total_number_text = "0 Threats & Exploits - MALFUNCTION_3 OFFLINE";
    }
    // STATUS INACTIVE NO LICENSE SENT YET.
    if ($response_key == 'INACTIVE') {
      $license_status = "INACTIVE LICENSE";
      $protection_total_number_text = "0 Threats & Exploits - INACTIVE OFFLINE";
    }

    // Alert Level Settings.
    $spammaster_alert_level = $config->get('spammaster.license_alert_level');
    // ALERT LEVEL, EMPTY.
    if (empty($spammaster_alert_level)) {
      $spammaster_alert_level_label = 'Empty data. ';
      $spammaster_alert_level_text = "No RBL (real-time blacklist) Server Sync";
      $spammaster_alert_level_p_label = "";
    }
    // ALERT LEVEL, MALFUNCTION_3.
    if ($spammaster_alert_level == 'MALFUNCTION_3') {
      $spammaster_alert_level_label = 'MALFUNCTION_3-> ';
      $spammaster_alert_level_text = "No RBL (real-time blacklist) Server Sync";
      $spammaster_alert_level_p_label = "";
    }
    // ALERT LEVEL, ALERT_0.
    if ($spammaster_alert_level == 'ALERT_0') {
      $spammaster_alert_level_label = 'Alert 0 -> ';
      $spammaster_alert_level_text = "Low level of spam and threats. Your website is mainly being visited by occasional harvester bots.";
      $spammaster_alert_level_p_label = " % percent probability";
    }
    // ALERT LEVEL, ALERT_1.
    if ($spammaster_alert_level == 'ALERT_1') {
      $spammaster_alert_level_label = 'Alert 1 -> ';
      $spammaster_alert_level_text = "Low level of spam and threats. Your website is mainly being visited by occasional human spammers and harvester bots.";
      $spammaster_alert_level_p_label = " % percent probability";
    }
    // ALERT LEVEL, ALERT_2.
    if ($spammaster_alert_level == 'ALERT_2') {
      $spammaster_alert_level_label = 'Alert 2 -> ';
      $spammaster_alert_level_text = "Medium level of spam and threats. Spam Master is actively fighting constant attempts of spam and threats by machine bots.";
      $spammaster_alert_level_p_label = " % percent probability";
    }
    // ALERT LEVEL, ALERT_3.
    if ($spammaster_alert_level == 'ALERT_3') {
      $spammaster_alert_level_label = 'Alert 3 -> ';
      $spammaster_alert_level_text = "WARNING! High level of spam and threats, flood detected. Spam Master is fighting an array of human spammers and bot networks which include exploit attempts.";
      $spammaster_alert_level_p_label = " % percent probability";
    }

    $form['license_header'] = [
      '#type' => 'details',
      '#title' => $this->t('<h3>License & Status</h3>'),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];

    // Insert license key field.
    $form['license_header']['license_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Insert license key number:'),
      '#default_value' => $config->get('spammaster.license_key'),
      '#description' => t('Insert your license key number. <a href="@spammaster_url">Get full rbl license</a>.', ['@spammaster_url' => 'https://www.techgasp.com/downloads/spam-master-license/']),
      '#attributes' => [
        'class' => [
          'spammaster-responsive-49',
        ],
      ],
    ];

    $spammaster_lic_controller = new SpamMasterLicController();
    $form['license_header']['submit'] = [
      '#type' => 'submit',
      '#attributes' => [
        'class' => ['button button--primary'],
      ],
      '#value' => t('Save & Refresh License'),
      '#submit' => [
        '::validateForm',
        '::submitForm',
        [$spammaster_lic_controller, 'spammasterlicmanualcreation'],
        '::spammasterrefesh',
      ],
    ];

    // Insert license table inside tree.
    $form['license_header']['license'] = [
      '#type' => 'table',
      '#responsive' => TRUE,
      '#attached' => [
        'library' => [
          'spammaster/spammaster-styles',
        ],
      ],
    ];
    // Insert addrow license status field.
    $form['license_header']['license']['addrow']['license_status'] = [
      '#disabled' => TRUE,
      '#type' => 'textarea',
      '#rows' => 2,
      '#title' => $this->t('Your licence status:'),
      '#default_value' => $config->get('spammaster.type') . ' -> ' . $license_status,
      '#description' => t('Your license status should always be <b>VALID</b>. <a href="@spammaster_url">About Statuses</a>.', ['@spammaster_url' => 'https://spammaster.techgasp.com/documentation/']),
    ];
    // Insert addrow alert level field.
    $form['license_header']['license']['addrow']['license_alert_level'] = [
      '#disabled' => TRUE,
      '#type' => 'textarea',
      '#rows' => 2,
      '#title' => $this->t('Your alert level:'),
      '#default_value' => $spammaster_alert_level_label . $spammaster_alert_level_text,
      '#description' => t('Your website alert level. <a href="@spammaster_url">About Alert Levels</a>.', ['@spammaster_url' => 'https://spammaster.techgasp.com/documentation/']),
    ];

    // Insert spam table inside tree.
    $form['license_header']['spam'] = [
      '#type' => 'table',
    ];
    // Insert addrow license status field.
    $form['license_header']['spam']['addrow']['license_protection'] = [
      '#disabled' => TRUE,
      '#type' => 'textfield',
      '#title' => $this->t('Your protection count (Threats & Exploits protection number):'),
      '#default_value' => $protection_total_number_text,
      '#attributes' => [
        'class' => [
          'spammaster-responsive',
        ],
      ],
    ];
    // Insert addrow alert level field.
    $form['license_header']['spam']['addrow']['license_probability'] = [
      '#disabled' => TRUE,
      '#type' => 'textfield',
      '#title' => $this->t('Your spam probability:'),
      '#default_value' => $config->get('spammaster.license_probability') . $spammaster_alert_level_p_label,
      '#attributes' => [
        'class' => [
          'spammaster-responsive',
        ],
      ],
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Get module config settings.
    $config = $this->config('spammaster.settings');
    if (empty($form_state->getValue('license_header')['license_key'])) {
      $form_state->setErrorByName('license_header', $this->t('License key can not be empty.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('spammaster.settings');
    $config->set('spammaster.license_key', $form_state->getValue('license_header')['license_key']);
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'spammaster.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function spammasterrefesh() {

    return header("Refresh:0");

  }

}
