<?php

namespace Drupal\lionbridge_translation_provider;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\tmgmt\Entity\Translator;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\TranslatorPluginUiBase;

/**
 * Lionbridge translator Ui.
 *
 * @todo Implement check if at least a single content type has multilanguage
 * support. Currently not necessary though.
 */
class LionbridgeTranslatorUi extends TranslatorPluginUiBase {

  /**
   * {@inheritdoc}
   */
  public function checkoutSettingsForm(array $form, FormStateInterface $form_state, JobInterface $job) {
    $form['project_title'] = [
      '#type' => 'textfield',
      '#title' => t('Project title'),
      '#size' => 60,
      '#default_value' => $job->label(),
    ];

    $form['secret'] = [
      '#type' => 'value',
      '#value' => !empty($job->settings['secret']) ? $job->settings['secret'] : Crypt::randomBytesBase64(32),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutInfo(JobInterface $job) {
    $translator = $job->getTranslator();
    $api_client = new LionbridgeConnector($translator);
    $quote = $api_client->getQuote($job->getReference());

    $form['translator'] = [
      '#type' => 'item',
      '#markup' => t('<strong>Translator:</strong> @plugin (@translator)', [
        '@plugin' => $translator->getPlugin()->getPluginDefinition()['label'],
        '@translator' => $translator->label(),
      ]),
    ];

    if (isset($quote['QuoteID'])) {
      $form['quote'] = [
        '#type' => 'item',
        '#title' => t('Quote: @quote_id', ['@quote_id' => $quote['QuoteID']]),
      ];
    }

    if (isset($quote['TotalCost']) && isset($quote['Currency'])) {
      // Account for empty values, which would set cost to 0.
      $cost = empty($quote['TotalCost']) ? 0 : $quote['TotalCost'];
      $form['quote']['cost'] = [
        '#type' => 'item',
        '#markup' => t('<strong>Total Cost:</strong> @cost @currency', [
          '@cost' => $cost,
          '@currency' => $quote['Currency'],
        ]),
      ];
    }

    if (isset($quote['Status'])) {
      $quote_status = $quote['Status'];

      if ($job->isFinished() && $quote['Status'] === LionbridgeConnector::QUOTE_STATUS_AUTHORIZED) {
        $quote_status .= ' - Translation is pending revision from the provider';
      }

      $form['quote']['status'] = [
        '#type' => 'item',
        '#markup' => t('<strong>Status:</strong> @status', ['@status' => $quote_status]),
      ];

      if ($quote['Status'] == LionbridgeConnector::QUOTE_STATUS_PENDING) {
        $form['actions']['authorize'] = [
          '#type' => 'submit',
          '#value' => t('Authorize quote'),
          '#submit' => ['_lionbridge_translation_provider_authorize_quote'],
          '#weight' => -9,
        ];
      }
    }

    if ($job->isActive()) {
      $form['actions']['poll'] = [
        '#type' => 'submit',
        '#value' => t('Poll translations'),
        '#submit' => ['_lionbridge_translation_provider_poll_submit'],
        '#weight' => -10,
      ];
    }

    if ($job->isFinished() && $translator->getSetting('service_type') != 'tm_update') {
      $form['actions']['redeliver'] = [
        '#type' => 'submit',
        '#value' => t('Force re-download'),
        '#submit' => ['_lionbridge_translation_provider_redeliver'],
        '#weight' => -10,
        '#suffix' => t('WARNING: Re-downloaded translations may not contain edits from the previous review.'),
      ];

      if (isset($quote['Status']) && $quote['Status'] != LionbridgeConnector::QUOTE_STATUS_COMPLETE) {
        $form['actions']['redeliver']['#attributes']['disabled'] = TRUE;
        $form['actions']['redeliver']['#suffix'] = FALSE;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    if (count(\Drupal::languageManager()->getLanguages()) === 1) {
      drupal_set_message(t(
        'Only one language has been detected, please <a href=":url_add">add new language</a> here.  To see list of languages, <a href=":url_list" target="_blank">click here</a>.',
        [
          ':url_add' => Url::fromRoute('language.add', [], ['query' => ['destination' => \Drupal::service('path.current')->getPath()]])->toString(),
          ':url_list' => Url::fromRoute('entity.configurable_language.collection')->toString(),
        ]
      ), 'warning');
    }

    $translator = $form_state->getFormObject()->getEntity();

    $form['endpoint'] = [
      '#type' => 'textfield',
      '#title' => t('Liondemand endpoint'),
      '#default_value' => $translator->getSetting('endpoint'),
      '#description' => t('Please enter your Lionbridge liondemand endpoint url.'),
      '#element_validate' => [[$this, 'validateEndpoint']],
      '#required' => TRUE,
    ];

    $form['access_key_id'] = [
      '#type' => 'textfield',
      '#title' => t('Lionbridge access key ID'),
      '#default_value' => $translator->getSetting('access_key_id'),
      '#description' => t('Please enter your Lionbridge Access Key ID.'),
      '#required' => TRUE,
      '#maxlength' => 20,
    ];

    $form['access_key'] = [
      '#type' => 'textfield',
      '#title' => t('Lionbridge access key'),
      '#default_value' => $translator->getSetting('access_key'),
      '#description' => t('Please enter your Lionbridge Access Key.'),
      '#required' => TRUE,
      '#maxlength' => 40,
    ];

    $form['po_number'] = [
      '#type' => 'textfield',
      '#title' => t('Purchase order number'),
      '#required' => TRUE,
      '#default_value' => $translator->getSetting('po_number'),
      '#description' => t('Please enter your Lionbridge Purchase Order Number.'),
    ];

    if ($translator->checkAvailable()->getSuccess()) {
      $api_client = new LionbridgeConnector($translator);

      $form['service_type'] = [
        '#type' => 'select',
        '#title' => t('Service Type'),
        '#options' => [
          'translation' => t('Translation'),
          'tm_update' => t('TM Update'),
        ],
        '#default_value' => $translator->getSetting('service_type'),
      ];

      $services = $api_client->listServices('');
      $options = [];
      foreach ($services['Service'] as $service) {
        $options[$service['ServiceID']] = $service['Name'];
      }
      $form['service'] = [
        '#type' => 'select',
        '#title' => t('Default translation service'),
        '#options' => $options,
        '#default_value' => $translator->getSetting('service'),
      ];

      $form['currency'] = [
        '#type' => 'select',
        '#title' => t('Default currency'),
        '#options' => array_combine($api_client->getAllowedCurrencies(), $api_client->getAllowedCurrencies()),
        '#default_value' => $translator->getSetting('currency'),
      ];

      $form['account_info'] = [
        '#type' => 'details',
        '#title' => t('Lionbridge account info'),
      ];

      $account_information = $api_client->accountInformation();
      unset($account_information['TargetLanguages']);
      foreach ($account_information as $key => $value) {
        if (is_array($value)) {
          $value = implode('<br />', $value);
        }

        $form['account_info'][$key] = [
          '#markup' => '<strong>' . $key . ':</strong> ' . $value . '<br>',
        ];
      }
    }

    return $form;
  }

  /**
   * Validate that the element is a valid endpoint.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateEndpoint(array $element, FormStateInterface &$form_state) {
    $value = $element['#value'];
    $value = filter_var($value, FILTER_SANITIZE_URL);
    $settings = $form_state->getValue('settings');
    $translator = Translator::create([
      'name' => 'temp_translator_for_lionbridge',
      'plugin' => 'lionbridge',
      'auto_accept' => FALSE,
      'status' => TRUE,
      'settings' => [
        'endpoint' => $settings['endpoint'],
        'access_key_id' => $settings['access_key_id'],
        'access_key' => $settings['access_key'],
        'po_number' => '123456',
        'service_type' => $settings['access_key'],
        'service' => '263',
        'currency' => 'USD',
      ],
      'remote_languages_mappings' => ['en' => 'en-us'],
    ]);

    $api_client = new LionbridgeConnector($translator);

    if ($value === '') {
      $form_state->setError($element, t('%name is required.', array('%name' => $element['#title'])));
    }
    elseif (substr($value, 0, 8) !== 'https://') {
      $form_state->setError($element, t('%name must start with <i>https://</i> - secure http protocol.  Missing or invalid protocol.', array('%name' => $element['#title'])));
    }
    elseif (filter_var($value, FILTER_VALIDATE_URL, array('flags' => FILTER_FLAG_HOST_REQUIRED)) === FALSE) {
      $form_state->setError($element, t('%name has <i>no</i> domain name.', array('%name' => $element['#title'])));
    }
    elseif (substr($value, -1) === '/') {
      $form_state->setError($element, t('%name must not end with a trailing slash.', array('%name' => $element['#title'])));
    }
    elseif (!filter_var($value, FILTER_VALIDATE_URL, array('flags' => FILTER_FLAG_PATH_REQUIRED)) === FALSE) {
      $form_state->setError($element, t('%name has path after the domain name. Please remove the path.', array('%name' => $element['#title'])));
    }
    elseif (!$this->pingEndpoint($value)) {
      $form_state->setError($element, t('%name URL is unreachable. Please verify endpoint URL.', array('%name' => $element['#title'])));
    }
    elseif ($translator->checkAvailable()->getSuccess()) {
      $account_info = $api_client->accountInformation();

      if (isset($account_info['Error'])) {
        $message = isset($account_info['Error']) ? $account_info['Error'] : t('Invalid endpoint.');
        $form_state->setErrorByName('', $message);
      }
    }
  }

  /**
   * Verifies that the endpoint is live.
   *
   * @param string $url
   *   The endpoint URL.
   *
   * @return bool
   *   Returns TRUE if the URL can be reached and FALSE otherwise.
   */
  public function pingEndpoint($url) {
    $curl_handle = curl_init($url);
    curl_setopt($curl_handle, CURLOPT_TIMEOUT, 5);
    curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
    curl_exec($curl_handle);
    $http_code = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
    curl_close($curl_handle);

    if (empty($http_code) || ($http_code < 200 && $http_code >= 400)) {
      return FALSE;
    }

    return TRUE;
  }

}
