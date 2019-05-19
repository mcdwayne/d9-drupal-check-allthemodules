<?php

namespace Drupal\skimlinks\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Component\Render\FormattableMarkup;
use GuzzleHttp\Exception\RequestException;

/**
 * Configure Skimlinks settings for this site.
 */
class SkimlinksAdminSettingsForm extends ConfigFormBase {

	/**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'skimlinks_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['skimlinks.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
  	$config = $this->config('skimlinks.settings');

  	$form['account'] = [
	    '#type' => 'fieldset',
	    '#title' => $this->t('General settings'),
      '#open' => TRUE,
	  ];

  	$form['account']['skimlinks_domainid'] = [
      '#default_value' => $config->get('domainid'),
      '#description' => $this->t(
        'This ID is unique to each site you affiliate with Skimlinks. Get your Domain ID on the <a href=":hub" target="_blank">Skimlinks Hub</a>. If you don\'t have a Skimlinks account you can apply for one <a href=":apply" target="_blank">here</a>.',
      	[
      		':hub' => Url::fromUri('https://hub.skimlinks.com/settings/install')->toString(),
      		':apply' => Url::fromUri('https://signup.skimlinks.com')->toString(),
      	]
     	),
      '#maxlength' => 20,
      // '#placeholder' => 'UA-',
      '#required' => TRUE,
      '#size' => 15,
      '#title' => $this->t('Domain ID'),
      '#type' => 'textfield',
      '#attributes' => [
        'placeholder' => t('000000X000000'),
      ],
    ];

    $form['account']['skimlinks_subdomain'] = [
      '#title' => t('Custom redirection sub domain'),
      '#type' => 'textfield',
      '#default_value'  => $config->get('subdomain') ?: 'https://go.redirectingat.com',
      '#description' => t(
        'You may use a custom subdomain to redirect your affiliate links rather than the default go.redirectingat.com. Please include the http:// or https://. Visit the <a href=":advanced" target="_blank">Skimlinks Advanced Settings</a> page for more details.',
        [
          ':advanced' => Url::fromUri('https://hub.skimlinks.com/settings/advanced')->toString(),
        ]
      )
    ];

    $form['skimlinks_environment'] = [
      '#type' => 'radios',
      '#title' => t('Environment'),
      '#options' => [t('Client side'), t('Server side')],
      '#description' => t('Client side uses Javascript, server side runs on the server.'),
      '#default_value' => $config->get('environment') ?: 0,
    ];

    $form['server_side'] = [
      '#type' => 'fieldset',
      '#title' => t('Server side settings'),
      '#states' => [
        'visible' => [
          ':input[name="skimlinks_environment"]' => ['value' => '1']
        ]
      ],
    ];

    $form['server_side']['api'] = [
      '#type' => 'fieldset',
      '#title' => t('API settings'),
    ];

    /**
     * @todo variables
     */
    $form['server_side']['api']['skimlinks_merchant_api_description'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . t(
        'The Skimlinks Merchant API provides information about Merchants participating in the Skimlinks program. The API is available via <code>:api</code>. <a href=":info" target="_blank">More information</a>',
        [
          ':api' =>  Url::fromUri('https://merchants.skimapis.com/v3/merchants')->toString(),
          ':info' => Url::fromUri('http://developers.skimlinks.com/merchant.html')->toString(),
        ]
      ) . '</p>'
    ];

    $form['server_side']['api']['skimlinks_merchant_api_endpoint'] = [
      '#type' => 'textfield',
      '#title' => t('API Endpoint'),
      '#description' => t('The API key for the Merchant API.'),
      '#default_value' => $config->get('merchant_api_endpoint') ?: 'https://merchants.skimapis.com/v3/merchants',
    ];

    $form['server_side']['api']['skimlinks_merchant_api_key'] = [
      '#type' => 'textfield',
      '#title' => t('API Key'),
      '#description' => t('The API key for the Merchant API.'),
      '#default_value' => $config->get('merchant_api_key'),
    ];

    $form['server_side']['api']['skimlinks_merchant_api_account_type'] = [
      '#type' => 'textfield',
      '#title' => t('Account type'),
      '#description' => t('The account type for the Merchant API.'),
      '#default_value' => $config->get('merchant_api_account_type'),
    ];

    $form['server_side']['api']['skimlinks_merchant_api_account_id'] = [
      '#type' => 'textfield',
      '#title' => t('Account Id'),
      '#description' => t('The account Id for the Merchant API.'),
      '#default_value' => $config->get('merchant_api_account_id'),
    ];

    $form['server_side']['skimlinks_api_cron_enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable Cron to update known domains using the Skimlinks Merchant API'),
      '#description' => t('Tick the box to turn on the cron job.'),
      '#default_value' => $config->get('api_cron_enabled') ?: 1,
    ];

    $form['server_side']['cron'] = [
      '#type' => 'fieldset',
      '#title' => t('Cron settings'),
      '#states' => [
        'visible' => [
          ':input[name="skimlinks_api_cron_enabled"]' => ['checked' => TRUE]
        ]
      ],
    ];

    $form['server_side']['cron']['skimlinks_cron_process_time'] = [
      '#type' => 'textfield',
      '#title' => t('Cron processing time (Seconds)'),
      '#size' => 4,
      '#description' => t('The number in seconds to spend when running cron.'),
      '#default_value' => (int) $config->get('cron_process_time') ?: 60,
    ];

    $form['server_side']['cron']['skimlinks_domains_update_threshold'] = [
      '#type' => 'textfield',
      '#title' => t('Cron update threshold (Minutes)'),
      '#size' => 4,
      '#description' => t('Used to control how often you want to update the list of known domains. Default: Every 12 hours.'),
      '#default_value' => (int) $config->get('domains_update_threshold') ?: 720,
    ];

    $form['server_side']['skimlinks_update_known_domains_on_entity_update'] = [
      '#type' => 'checkbox',
      '#title' => t('Update known domains when content changes'),
      '#description' => t('Tick the box to update the kwnon domains list when content is saved. The list is automatically populated by the Merchant API.'),
      '#default_value' => $config->get('update_known_domains_on_entity_update') ?: 1,
    ];

    $form['server_side']['skimlinks_domain_blacklist'] = [
      '#type' => 'textarea',
      '#title' => t('Domain blacklist'),
      '#description' => t('These domains will not be altered by skimlinks module. Please enter one domain per line. i.e. example.com. You do not need to specify the protocol.'),
      '#default_value' => implode("\n", skimlinks_domain_blacklist()),
    ];

    $form['server_side']['skimlinks_link_new_window'] = [
      '#type' => 'checkbox',
      '#title' => t('Open links in a new window'),
      '#description' => t('Tick the box to make the links open on a new window.'),
      '#default_value' => $config->get('link_new_window') ?: 1,
    ];

    $form['server_side']['skimlinks_link_nofollow'] = [
      '#type' => 'checkbox',
      '#title' => t('Make links nofollow'),
      '#description' => t('Tick the box to make the links nofollow.'),
      '#default_value' => $config->get('link_nofollow') ?: 1,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   * @todo Improve domain id validation.
   * @todo Validation for server-side fields
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Trim whitespace
    $form_state->setValue('skimlinks_domainid', trim($form_state->getValue('skimlinks_domainid')));
    $form_state->setValue('skimlinks_subdomain', trim($form_state->getValue('skimlinks_subdomain')));

    // Domain ID

    // Ensure the skimlinks domain ID consists of only numbers and letters
    if (!preg_match('/^[a-zA-Z0-9]*$/', $form_state->getValue('skimlinks_domainid'))) {
      $form_state->setErrorByName('skimlinks_domainid', t('A valid Domain ID should have the following format: 000000X000000'));
      return FALSE;
    }

    // Custom redirection sub domain

    // Check the user has included the URL schema in the subdomain value.
    $subdomain = $form_state->getValue('skimlinks_subdomain');
    preg_match('/^https?:\/\//', $subdomain, $matches);
    $protocol = !empty($matches) ? $matches[0] : false;
    if (!$protocol) {
      $form_state->setErrorByName('skimlinks_subdomain', t('Your custom redirection sub-domain is not a valid URL. Please include the http:// or https://'));
    }

    // Validate the provided subdomain by comparing the Skimlinks default
    // response with the new subdomain response
    $standard_url = 'https://go.redirectingat.com/check/domain_check.html';
    $cnamecheck_url = $subdomain . '/check/domain_check.html';
    try {
      $original = \Drupal::httpClient()->get($standard_url, ['http_errors' => FALSE])->getBody()->__toString();
    }
    catch (RequestException $e) {
      $form_state->setErrorByName('skimlinks_subdomain', t('We\'re sorry, but we can\'t connect to the Skimlinks server at the moment. Please try again later'));
    }
    try {
      $new = \Drupal::httpClient()->get($cnamecheck_url, ['http_errors' => FALSE])->getBody()->__toString();
    }
    catch (RequestException $e) {
      $form_state->setErrorByName('skimlinks_subdomain', t('The custom domain does not appear to be a valid URL.'));
    }
    if (empty($new) || $original !== $new) {
      $form_state->setErrorByName('skimlinks_subdomain', t('Your custom redirection sub-domain is not currently pointing at Skimlinks servers.'));
    }

    $blacklist = $form_state->getValue('skimlinks_domain_blacklist');
    if (!skimlinks_validate_blacklist($blacklist)) {
      /**
       * @todo limit variable
       */
      $form_state->setErrorByName('skimlinks_domain_blacklist', t('There is a problem with one of the submitted blacklist domains. Due to certain database limitations, domains must have :limit characters or fewer.', [':limit' => _skimlinks_max_domain_length()]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('skimlinks.settings');
    // Remove all but user input
    $form_state->cleanValues();
    // Retrieve the blacklist for adding to the DB
    $blacklist = $form_state->getValue('skimlinks_domain_blacklist');
    $form_state->unsetValue('skimlinks_domain_blacklist');
    skimlinks_create_blacklist($blacklist);

    // Set config for all others
    foreach ($form_state->getValues() as $key => $value) {
      $config_key = preg_replace('/^skimlinks_/', '', $key);
      $config->set($config_key, $value);
    }

    $config->save();
	  _drupal_flush_css_js();

    parent::submitForm($form, $form_state);
  }
}
