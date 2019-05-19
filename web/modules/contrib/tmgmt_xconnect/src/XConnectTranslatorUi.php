<?php

/**
 * @file
 * Contains \Drupal\tmgmt_xconnect\XConnectTranslatorUi.
 */

namespace Drupal\tmgmt_xconnect;

use Drupal\Component\Utility\Xss;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\TranslatorPluginUiBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * X-Connect translator UI.
 */
class XConnectTranslatorUi extends TranslatorPluginUiBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();

    // The order.xml settings.
    $settings_order = $translator->getSetting('order');
    $form['order'] = array(
      '#type' => 'details',
      '#title' => t('Order'),
      '#open' => true,
    );
    $form['order']['client_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Client ID'),
      '#description' => t('The client ID to order the translations for.'),
      '#default_value' => $settings_order['client_id'],
      '#required' => true,
    );
    $form['order']['is_confidential'] = array(
      '#type' => 'checkbox',
      '#title' => t('Is the content for the translation confidential?'),
      '#default_value' => $settings_order['is_confidential'],
    );
    $form['order']['needs_confirmation'] = array(
      '#type' => 'checkbox',
      '#title' => t('Should a confirmation be sent when the translation is ready?'),
      '#default_value' => $settings_order['needs_confirmation'],
    );
    $form['order']['needs_quotation'] = array(
      '#type' => 'checkbox',
      '#title' => t('Should a quotation be created and sent before the translation is performed?'),
      '#default_value' => $settings_order['needs_quotation'],
    );

    // The connection settings.
    $settings_connection = $translator->getSetting('connection');
    $form['connection'] = array(
      '#type' => 'details',
      '#title' => t('Connection'),
      '#description' => t('Here you can specify the connection parameters for the AMPLEXOR Translation Service.'),
      '#open' => TRUE,
    );
    $form['connection']['protocol'] = array(
      '#type' => 'radios',
      '#title' => t('Protocol'),
      '#description' => t('The protocol to use to connect to the FTP server.'),
      '#options' => array(
        'FTP' => t('FTP'),
        'SFTP' => t('SFTP'),
      ),
      '#default_value' => $settings_connection['protocol'],
    );
    $form['connection']['port'] = array(
      '#type' => 'number',
      '#title' => t('Port'),
      '#min' => 1,
      '#max' => 65535,
      '#step' => 1,
      '#description' => t('The port number to use to connect to the FTP server (e.g. 21 for FTP, 22 for sFTP).'),
      '#default_value' => $settings_connection['port'],
      '#required' => true,
    );
    $form['connection']['timeout'] = array(
      '#type' => 'number',
      '#title' => t('Timeout'),
      '#min' => 10,
      '#max' => 60000,
      '#step' => 10,
      '#description' => t('The timeout in milliseconds (e.g. 90).'),
      '#default_value' => $settings_connection['timeout'],
      '#required' => true,
    );
    $form['connection']['hostname'] = array(
      '#type' => 'textfield',
      '#title' => t('Hostname'),
      '#description' => t('The FTP server to connect to (e.g. ftp://hostname.domain).'),
      '#default_value' => $settings_connection['hostname'],
      '#required' => true,
    );
    $form['connection']['username'] = array(
      '#type' => 'textfield',
      '#title' => t('Username'),
      '#description' => t('The username to connect to the FTP server.'),
      '#default_value' => $settings_connection['username'],
      '#required' => true,
    );
    $form['connection']['password'] = array(
      '#type' => 'textfield',
      '#title' => t('Password'),
      '#description' => t('The password to connect to the FTP server.'),
      '#default_value' => $settings_connection['password'],
      '#required' => true,
    );
    $form['connection']['directory_send'] = array(
      '#type' => 'textfield',
      '#title' => t('Request folder'),
      '#description' => t('The folder to store the translation requests on the FTP server (e.g. To_LSP).'),
      '#default_value' => $settings_connection['directory_send'],
      '#required' => true,
    );
    $form['connection']['directory_send_processed'] = array(
      '#type' => 'textfield',
      '#title' => t('Processed request folder'),
      '#description' => t('The folder to store the processed translation requests on the FTP server (e.g. To_LSP).'),
      '#default_value' => $settings_connection['directory_send_processed'],
      '#required' => true,
    );
    $form['connection']['directory_receive'] = array(
      '#type' => 'textfield',
      '#title' => t('Receive folder'),
      '#description' => t('The folder to receive the translation requests from on the FTP server (e.g. From_LSP).'),
      '#default_value' => $settings_connection['directory_receive'],
      '#required' => true,
    );
    $form['connection']['directory_receive_processed'] = array(
      '#type' => 'textfield',
      '#title' => t('Processed receive folder'),
      '#description' => t('The folder to store received requests from on the FTP server (e.g. From_LSP).'),
      '#default_value' => $settings_connection['directory_receive_processed'],
      '#required' => true,
    );

    // Cron settings.
    $settings_cron = $translator->getSetting('cron');
    $form['cron'] = array(
      '#type' => 'details',
      '#title' => t('Cron'),
      '#description' => t('Here you can specify the cron settings.'),
      '#open' => TRUE,
    );
    $form['cron']['status'] = array(
      '#type' => 'checkbox',
      '#title' => t('Receive translated jobs during cron.'),
      '#description' => t('Enable to receive the translated jobs during cron, leave disabled to receive them manually.'),
      '#default_value' => $settings_cron['status'],
    );
    $form['cron']['limit'] = array(
      '#type' => 'select',
      '#title' => t('Limit'),
      '#description' => t('Maximum number of files to process during cron run.'),
      '#options' => array(
        0 => t('No limit'),
        1 => 1,
        2 => 2,
        5 => 5,
        10 => 10,
        20 => 20,
        50 => 50,
        100 => 100,
        200 => 200,
        500 => 500,
        1000 => 1000,
      ),
      '#default_value' => $settings_cron['limit'],
      '#required' => true,
    );

    // Debug settings.
    $settings_debug = $translator->getSetting('debug');
    $form['debug'] = array(
      '#type' => 'details',
      '#title' => t('Debug'),
      '#description' => t('Here you can specify the debug settings.'),
      '#open' => $settings_debug['status'],
    );

    $form['debug']['status'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable debug mode.'),
      '#description' => t('Check to enable debug mode.'),
      '#default_value' => $settings_debug['status'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutSettingsForm(array $form, FormStateInterface $form_state, JobInterface $job) {
    $form['instructions'] = array(
      '#type' => 'textarea',
      '#title' => t('Instructions'),
      '#description' => t('Add optional instructions for the translator(s).'),
    );
    $form['reference'] = array(
      '#type' => 'textfield',
      '#title' => t('Reference'),
      '#description' => t('Add an optional reference to the translation order, this will be used in communication about the translation job.'),
    );
    $form['due_date'] = array(
      '#type' => 'number',
      '#title' => t('Due date'),
      '#min' => 0,
      '#step' => 1,
      '#description' => t('Number of days to the deadline for this translation job.'),
      '#default_value' => 0,
      '#required' => true,
    );
    $form['issued_by'] = array(
      '#type' => 'email',
      '#title' => t('Issued by'),
      '#description' => t('The email address of the issuer of the translation job.'),
      '#default_value' => \Drupal::currentUser()->getEmail(),
      '#required' => true,
    );
    return parent::checkoutSettingsForm($form, $form_state, $job);
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutInfo(JobInterface $job) {
    // If the job is finished, it's not possible to import translations anymore.
    if ($job->isFinished()) {
      return parent::checkoutInfo($job);
    }
    $form = array(
      '#type' => 'fieldset',
      '#title' => t('X-Connect information'),
    );

    // Show the Job settings (if any).
    if (!empty($job->getSetting('instructions'))) {
      $form['instructions'] = array(
        '#type' => 'item',
        '#title' => t('Instructions'),
        '#markup' => check_markup(Xss::filter($job->getSetting('instructions'))),
      );
    }
    if (!empty($job->getSetting('reference'))) {
      $form['reference'] = array(
        '#type' => 'item',
        '#title' => t('Reference'),
        '#markup' => Xss::filter($job->getSetting('reference')),
      );
    }
    return $this->checkoutInfoWrapper($job, $form);
  }

}
