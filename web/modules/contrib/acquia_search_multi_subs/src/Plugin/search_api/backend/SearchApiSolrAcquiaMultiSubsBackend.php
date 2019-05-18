<?php

namespace Drupal\acquia_search_multi_subs\Plugin\search_api\backend;

use Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend;
use Drupal\Core\Config\Config;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\acquia_search_multi_subs\EventSubscriber\SearchSubscriber;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\acquia_connector\Client;
use Drupal\acquia_connector\CryptConnector;
use Symfony\Component\Validator\Constraints\False;


/**
 * @SearchApiBackend(
 *   id = "search_api_solr_acquia_multi_subs",
 *   label = @Translation("Acquia Solr Multi Sub"),
 *   description = @Translation("Index items using a specific Acquia Apache Solr search server.")
 * )
 */
class SearchApiSolrAcquiaMultiSubsBackend extends SearchApiSolrBackend {

  protected $eventDispatcher = FALSE;
  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ModuleHandlerInterface $module_handler, Config $search_api_solr_settings, LanguageManagerInterface $language_manager) {

    // If we have a particular core selected, then construct the index
    // configuration accordingly.

    // Shortcut to the override configuration.
    $override = $configuration['acquia_override_subscription'];

    // If auto detection was enabled, we can ignore all other settings.
    if (!empty($override['acquia_override_auto_switch']) && $override['acquia_override_auto_switch'] == TRUE) {
      // Do the magic env specific detection here.
      $configuration['host'] = acquia_search_get_search_host();
      $configuration['path'] = '/solr/';
      $configuration['core'] = isset($_ENV['AH_SITE_ENVIRONMENT']) ? $this->getEnvironmentCore() : $override['local_core'];
    }
    else if (!empty($override['acquia_override_selector'])) {
      $configuration['host'] = acquia_search_get_search_host();
      // Attention! We do not need to add the core to the path, because the core property
      // will inherit the core property. @see Endpoint::getBaseUri().
      // The core property is passed in our connect method, becasue we pass
      // the configuration of this backend to the plugin.
      $configuration['path'] = '/solr/';
      $configuration['core'] = isset($_ENV['AH_SITE_ENVIRONMENT']) ? $override['acquia_override_selector'] : $override['local_core'];
    }
    else if (!empty($override['acquia_override_subscription_id']) &&
      !empty($override['acquia_override_subscription_key']) &&
      !empty($override['acquia_override_subscription_corename'])) {
      // Manual override.
    }
    // No override is in use.
    else {
      $configuration['host'] = acquia_search_get_search_host();
      $configuration['path'] = '/solr/' . \Drupal::config('acquia_connector.settings')->get('identifier');
    }

    if ($configuration['scheme'] == 'https') {
      $configuration['port'] = 443;
    }
    else {
      $configuration['port'] = 80;
    }

    return parent::__construct($configuration, $plugin_id, $plugin_definition, $module_handler, $search_api_solr_settings, $language_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('config.factory')->get('search_api_solr.settings'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewSettings() {
    $info = parent::viewSettings();

    $auto_detection = (isset($this->configuration['acquia_override_subscription']['acquia_override_auto_switch']) && $this->configuration['acquia_override_subscription']['acquia_override_auto_switch']);
    $auto_detection_state = ($auto_detection) ? $this->t('enabled') : $this->t('disabled');

    // Provide a detailed message about the environment the module is detecting.
    if (isset($_ENV['AH_SITE_ENVIRONMENT'])) {
      $info_text = $this->t('Auto detection of your environment is %state. Detected environment: :env, site name: :site_name.: ',
        array(
          '%state' => $auto_detection_state,
          ':env' => $_ENV['AH_SITE_ENVIRONMENT'],
          ':site_name' => $_ENV['AH_SITE_NAME'],
        )
      );
    }
    else {
      $info_text = $this->t('Auto detection of your environment is %state. Detecting local environment.',
        array('%state' => $auto_detection_state));
    }

    $info[] = array(
      'label' => $this->t('Acquia Search Auto Detection'),
      'info' => $info_text,
    );

    return $info;
  }

  /**
   * Creates a connection to the Solr server as configured in $this->configuration.
   *
   * We need to override the endpoint to enable environment specific detection.
   */
  protected function connect() {
    parent::connect();
    if (!$this->eventDispatcher) {
      $this->eventDispatcher = $this->solr->getEventDispatcher();
      $plugin = new SearchSubscriber();
      $this->solr->registerPlugin('acquia_solr_search_subscriber', $plugin, $this->configuration['acquia_override_subscription']);
      // Don't use curl.
      $this->solr->setAdapter('Solarium\Core\Client\Adapter\Http');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['host']['#access'] = FALSE;
    $form['port']['#access'] = FALSE;
    $form['path']['#access'] = FALSE;

    // Define the override form.
    $form['acquia_override_subscription'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Configure Acquia Search'),
      '#collapsed' => FALSE,
      '#collapsible' => TRUE,
      '#tree' => TRUE,
      '#weight' => -10,
      '#element_validate' => array('acquia_search_multi_subs_form_validate'),
    );

    // Add a checkbox to auto switch per environment.
    $form['acquia_override_subscription']['acquia_override_auto_switch'] = array(
      '#weight' => -10,
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically switch when an Acquia Environment is detected'),
      '#description' => $this->t('Based on the detection of the AH_SITE_NAME and
        AH_SITE_ENVIRONMENT header we can detect which environment you are currently
        using and switch the Acquia Search Core automatically if there is a corresponding core.
        Make sure to <a href=":url">update your locally cached subscription information</a> if your core does not show up.',
        array(':url' => Url::fromRoute('acquia_connector.refresh_status')->toString())),
      '#default_value' => $this->configuration['acquia_override_subscription']['acquia_override_auto_switch'],
    );

    $options = array('default' => t('Default'), 'other' => t('Other'));

    $subscription = \Drupal::config('acquia_connector.settings')->get('subscription_data');
    $search_cores = $subscription['heartbeat_data']['search_cores'];

    $failover_exists = NULL;
    $failover_region = NULL;
    if (is_array($search_cores)) {
      foreach ($search_cores as $search_core) {
        $options[$search_core['core_id']] = $search_core['core_id'];
        if (strstr($search_core['core_id'], '.failover')) {
          $failover_exists = TRUE;
          $matches = array();
          preg_match("/^([^-]*)/", $search_core['balancer'], $matches);
          $failover_region = reset($matches);
        }
      }
    }
    $form['acquia_override_subscription']['acquia_override_selector'] = array(
      '#type' => 'select',
      '#title' => t('Acquia Search Core'),
      '#options' => $options,
      '#default_value' => $this->configuration['acquia_override_subscription']['acquia_override_selector'],
      '#description' => $this->t('Choose a search core to connect to. This is usually not necessary unless you really
        want this search environment to connect to a different Acquia search subscription.
        By default it uses your subscription that was configured for the
        <a href=":url">Acquia Connector</a>.', array(':url' => Url::fromRoute('acquia_connector.settings')->toString())),
      '#states' => array(
        'visible' => array(
          ':input[name*="acquia_override_auto_switch"]' => array('checked' => FALSE),
        ),
      ),
    );

    $options = array();
    if (is_array($search_cores)) {
      foreach ($search_cores as $search_core) {
        $options[$search_core['core_id']] = $search_core['core_id'];
        if (strstr($search_core['core_id'], '.failover')) {
          $failover_exists = TRUE;
          $matches = array();
          preg_match("/^([^-]*)/", $search_core['balancer'], $matches);
          $failover_region = reset($matches);
        }
      }
    }
    $form['acquia_override_subscription']['local_core'] = array(
      '#weight' => 10,
      '#type' => 'select',
      '#description' => t('Please enter the name of the search core you would like to use on your local environments, e.g. for development reasons.'),
      '#title' => t('Core to use when site is running inside non-Acquia (local) environment'),
      '#options' => $options,
      '#default_value' => $this->configuration['acquia_override_subscription']['local_core'],
    );

    // Show a warning if there are not enough cores available to make the auto
    // switch possible.
    if (count($options) < 2) {
      drupal_set_message($this->t('It seems you only have 1 Acquia Search index.
      To find out if you are eligible for a search core per environment it is
      recommended you open a support ticket with Acquia. Once you have that settled,
      <a href=":url">refresh</a> your subscription so it pulls in the latest information to connect
      to your indexes.',
        array(':url' => Url::fromRoute('acquia_connector.refresh_status')->toString())), 'warning', FALSE);
    }

    // Generate the custom form.
    $form['acquia_override_subscription']['acquia_override_subscription_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Enter your Acquia Subscription Identifier'),
      '#description' => t('Prefilled with the identifier of the Acquia Connector. You can find your details in Acquia Insight.'),
      '#default_value' => $this->configuration['acquia_override_subscription']['acquia_override_subscription_id'],
      '#states' => array(
        'visible' => array(
          ':input[name*="acquia_override_selector"]' => array('value' => 'other'),
          ':input[name*="acquia_override_auto_switch"]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['acquia_override_subscription']['acquia_override_subscription_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Enter your Acquia Subscription key'),
      '#description' => t('Prefilled with the key of the Acquia Connector. You can find your details in Acquia Insight.'),
      '#default_value' => $this->configuration['acquia_override_subscription']['acquia_override_subscription_key'],
      '#states' => array(
        'visible' => array(
          ':input[name*="acquia_override_selector"]' => array('value' => 'other'),
          ':input[name*="acquia_override_auto_switch"]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['acquia_override_subscription']['acquia_override_subscription_corename'] = array(
      '#type' => 'textfield',
      '#description' => t('Please enter the name of the Acquia Search core you want to connect to that belongs to the above identifier and key. In most cases you would want to use the dropdown list to get the correct value.'),
      '#title' => t('Enter your Acquia Search Core Name'),
      '#default_value' => $this->configuration['acquia_override_subscription']['acquia_override_subscription_corename'],
      '#states' => array(
        'visible' => array(
          ':input[name*="acquia_override_selector"]' => array('value' => 'other'),
          ':input[name*="acquia_override_auto_switch"]' => array('checked' => FALSE),
        ),
      ),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   * Method to save the configuration.
   *
   * We only save the index details, when the backend is overwritten, either
   * by providing the exact index details manually, or when the user chose
   * one of the available indices from the dropdown list.
   *
   * In auto switch mode we only save the mode boolean flag.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValues();

    // If we do not have auto switch enabled, statically configure the right
    // core to options.

    $has_id = (isset($values['acquia_override_subscription']['acquia_override_subscription_id'])) ? TRUE : FALSE;
    $has_key = (isset($values['acquia_override_subscription']['acquia_override_subscription_key'])) ? TRUE : FALSE;
    $has_corename = (isset($values['acquia_override_subscription']['acquia_override_subscription_corename'])) ? TRUE : FALSE;
    $has_auto_switch = !empty($values['acquia_override_subscription']['acquia_override_auto_switch']) ? TRUE : FALSE;

//
//    // Static override for the index, save the provided core information.
//    if (!$has_auto_switch && $has_id && $has_key && $has_corename) {
//      $identifier = $values['acquia_override_subscription']['acquia_override_subscription_id'];
//      $key = $values['acquia_override_subscription']['acquia_override_subscription_key'];
//      $corename = $values['acquia_override_subscription']['acquia_override_subscription_corename'];
//
//      // Set our solr path
//      $this->options['path'] = '/solr/' . $corename;
//
//      // Set the derived key for this environment.
//      // Subscription already cached by configurationFormValidate().
//      $subscription = $this->getAcquiaSubscription($identifier, $key);
//      $derived_key_salt = $subscription['derived_key_salt'];
//      $derived_key = _acquia_search_multi_subs_create_derived_key($derived_key_salt, $corename, $key);
//      $this->options['derived_key'] = $derived_key;
//
//      $search_host = acquia_search_multi_subs_get_hostname($corename);
//      $this->options['host'] = $search_host;
//    }
  }

  private function getEnvironmentCore() {
    $ah_site_environment = isset($_ENV['AH_SITE_ENVIRONMENT']) ? $_ENV['AH_SITE_ENVIRONMENT'] : '';
    $ah_site_name = isset($_ENV['AH_SITE_NAME']) ? $_ENV['AH_SITE_NAME'] : '';
    $ah_site_group = isset($_ENV['AH_SITE_GROUP']) ? $_ENV['AH_SITE_GROUP'] : '';
    $ah_region = isset($_ENV['AH_CURRENT_REGION']) ? $_ENV['AH_CURRENT_REGION'] : '';
    $ah_db_name = '';
    if ($ah_site_environment && $ah_site_name && $ah_site_group) {
      $tmp = \Drupal\Core\Database\Database::getConnection()->getConnectionOptions();
      $ah_db_name = $tmp['database'];
    }

    $conf_path = \Drupal::service('site.path');
    $sites_foldername = substr($conf_path, strrpos($conf_path, '/') + 1);

    $acquia_identifier = \Drupal::config('acquia_connector.settings')->get('identifier');

    $subscription_expected_search_cores = $this->getExpectedSearchCores($acquia_identifier, $ah_site_environment, $ah_site_name, $ah_site_group, $sites_foldername, $ah_db_name);

    // Retrieve the list of search cores availablle.
    $subscription = \Drupal::config('acquia_connector.settings')->get('subscription_data');
    $available_search_cores = $subscription['heartbeat_data']['search_cores'];

    $match_found = FALSE;
    foreach ($subscription_expected_search_cores as $expected_core_name) {
      // This allows us to break from the 2-level deep foreach.
      if ($match_found) {
        break;
      }
      // Loop over all the available search cores.
      foreach ($available_search_cores as $available_search_core) {
        if (strtolower($available_search_core['core_id']) == strtolower($expected_core_name)) {
          $core = $available_search_core['core_id'];
          $match_found = TRUE;
          break;
        }
      }
    }
    return $core;
  }

  /**
   * Calculates eligible search core names based on environment information,
   * in order of most likely (or preferred!) core names first.
   *
   * The generated list of expected core names is done according to Acquia Search
   * conventions, prioritized in this order:
   * WXYZ-12345.[env].[databasename]
   * WXYZ-12345.[env].[sitegroup]
   * WXYZ-12345.[env].[sitefolder]
   * WXYZ-12345.[env].default
   * WXYZ-12345_[sitename][env]
   * WXYZ-12345.dev.[databasename] (only if $ah_site_environment isn't 'prod')
   * WXYZ-12345.dev.[sitefolder] (only if $ah_site_environment isn't 'prod')
   * WXYZ-12345_[sitename]dev    (only if $ah_site_environment isn't 'prod')
   * WXYZ-12345                  (only if $ah_site_environment is 'prod')
   *
   * NOTE that [sitefolder] is a stripped-down version of the sites/* folder,
   * such that it is only alphanumeric and max. 16 chars in length.
   * E.g. for sites/www.example.com, the expected corename for a dev environment
   * could be WXYZ-12345.dev.wwwexamplecom
   *
   * @param string $acquia_identifier
   *   Subscription ID. E.g. WXYZ-12345
   * @param string $ah_site_environment
   *   String with the environment, from $_ENV[AH_SITE_ENVIRONMENT].
   *   E.g. 'dev', 'test', 'prod'.
   * @param string $ah_site_name
   *   The name of the site (includes some form of environment info, from $_ENV['AH_SITE_NAME'].
   * @param string $ah_site_group
   *   From $_ENV['AH_SITE_GROUP'].
   * @param string $sites_foldername
   *   Optional. The current site folder within [docroot]/sites/*.
   *   @see conf_path()
   * @return array
   *   The eligible core_ids sorted by best match first.
   */
  private function getExpectedSearchCores($acquia_identifier, $ah_site_environment, $ah_site_name, $ah_site_group, $sites_foldername = 'default', $ah_db_name) {
    // Build eligible environments array.
    $ah_environments = array();
    $expected_core_names = array();

    // If we have the proper environment, add it as the first option.
    if ($ah_site_environment) {
      $ah_environments[$ah_site_environment] = $ah_site_name;
    }
    // Add fallback options. For sites that lack the AH_* variables or are non-prod
    // we will try to match .dev.[sitegroup] cores.
    if ($ah_site_environment != 'prod' && $ah_site_environment != '01live') {
      $ah_environments['dev'] = $ah_site_group;
      // Build the CORE.env.site_group default.
      $expected_core_names[] = $acquia_identifier . '.' . $ah_site_environment . '.' . $ah_site_group;
    }

    foreach ($ah_environments as $site_environment => $site_name) {
      // The possible core name suffixes are [database name], [current site folder name] and 'default'.
      $core_suffixes = array_unique(array($ah_db_name, $sites_foldername, 'default', $ah_site_name));
      foreach ($core_suffixes as $core_suffix) {
        if ($core_suffix) {
          // Fix the $core_suffix: alphanumeric only
          $core_suffix = preg_replace('@[^a-zA-Z0-9]+@', '', $core_suffix);
          // We first add a 60-char-length indexname, which is the Solr index name limit.
          $expected_core_names[] = substr($acquia_identifier . '.' . $site_environment . '.' . $core_suffix, 0, 60);
          // Before 17-nov-2015 (see BZ-2778) the suffix limit was 16 chars; add this as well for backwards compatibility.
          $expected_core_names[] = $acquia_identifier . '.' . $site_environment . '.' . substr($core_suffix, 0, 16);
        }
      }
      // Add WXYZ-12345_[sitename][env] option.
      if (!empty($site_name) && $sites_foldername == 'default') {
        // Replace any weird characters that might appear in the sitegroup name or
        // identifier.
        $site_name = preg_replace('@[^a-zA-Z0-9_-]+@', '_', $site_name);
        $expected_core_names[] = $acquia_identifier . '_' . $site_name;
      }
      // Add our failover options
      $expected_core_names[] = $acquia_identifier . '.' . $site_environment . '.failover';
    }
    // Add suffix-less core if we're on prod now. If the sitename is empty,
    // it means we are not on Acquia Hosting or something is wrong. Do not
    // allow the prod index to be one of the available cores.
    if ($ah_site_environment == 'prod' && $ah_site_name != '') {
      $expected_core_names[] = $acquia_identifier;
    }

    return array_unique($expected_core_names);
  }
}
