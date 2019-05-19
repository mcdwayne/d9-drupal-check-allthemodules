<?php

namespace Drupal\toolshed_search\Plugin\SolrConnector;

use Solarium\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\search_api_solr\Plugin\SolrConnector\StandardSolrConnector;
use Drupal\search_api_solr\SearchApiSolrException;

/**
 * Standard Solr connector.
 *
 * @SolrConnector(
 *   id = "drupal_settings",
 *   label = @Translation("Settings File Config"),
 *   description = @Translation("A connector that uses host settings from the site's settings.php file.")
 * )
 */
class SettingsSolrConnector extends StandardSolrConnector implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Settings $settings, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->settings = $settings;
    try {
      $this->configuration = $this->generateConfiguration($this->configuration);
    }
    catch (SearchApiSolrException $e) {
      // If there was a settings error, log it and prevent use of these options.
      $logger->error($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('settings'),
      $container->get('logger.factory')->get('settings_solr_connector')
    );

    $translation = $container->get('string_translation');
    $plugin->setStringTranslation($translation);

    return $plugin;
  }

  /**
   * Resolves the 'solr_instance' configuration value into Solr host settings.
   *
   * @param array $configuration
   *   Configuration to use in as defaults and to determine which set of
   *   settings to use from the Drupal settings.
   *
   * @return array
   *   The a configuration with the settings applied if the appropriate settings
   *   could be found with defaults applied. If no settings were found, it
   *   will just return the original settings back.
   */
  protected function generateConfiguration(array $configuration) {
    $instances = $this->settings->get('solr_core_connector.hosts');
    $instance = isset($configuration['solr_instance']) ? $configuration['solr_instance'] : '';

    $settings = [];
    if (!empty($instances[$instance])) {
      $solrConfig = $instances[$instance] + parent::defaultConfiguration();
      $violations = [];

      // Validate the protocol scheme type.
      if (!preg_match('#https?#', $solrConfig['scheme'])) {
        $violations[] = $this->t('Only "http" and "https" are supported as scheme types.');
      }
      else {
        $settings['scheme'] = $solrConfig['scheme'];
      }

      // Validate the host name.
      if (preg_match('#[^\w\-_.]#', $solrConfig['host'])) {
        $violations[] = $this->t('Provider host should only contain letters, numbers, periods, hyphens or underscores.');
      }
      else {
        $settings['host'] = $solrConfig['host'];
      }

      // Validate the Solr path setting.
      if (strpos($solrConfig['path'], '/') !== 0) {
        $violations[] = $this->t('Provider path should start with a "%i"', ['%i' => '/']);
      }
      else {
        $settings['path'] = $solrConfig['path'];
      }

      // Validate a Solr core name.
      if (preg_match('#^/|[^\w\-_]#', $solrConfig['core'], $matches)) {
        $violations[] = (strpos($matches[0], '/') === 0)
          ? $this->t('Provider core should not start with "%i".', ['%i' => '/'])
          : $this->t('Provider core should only contain letters, numbers, hyphens or underscores.');
      }
      else {
        $settings['core'] = $solrConfig['core'];
      }

      $port = intval($solrConfig['port']);
      if ($port > 0 && $port < 65535) {
        $settings['port'] = $port;
      }
      else {
        $violations[] = $this->t('Invalid port specified.');
      }

      if (!empty($violations)) {
        throw new SearchApiSolrException($this->t(
          "Format validation for the following Solr hosting settings: @violations",
          ['@violations' => Markup::create('<ul><li>' . implode("<br/> - ", $violations) . '</li></ul>')]
        ));
      }
    }

    return $settings + $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'solr_instance' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $instances = $this->settings->get('solr_core_connector.hosts', []);

    $solrOpts = [];
    foreach ($instances as $key => $solrInstance) {
      $solrOpts[$key] = $solrInstance['label'];
    }

    $form['solr_instance'] = [
      '#type' => 'select',
      '#title' => $this->t('Solr instance settings'),
      '#required' => TRUE,
      '#weight' => -5,
      '#options' => $solrOpts,
      '#default_value' => $this->configuration['solr_instance'],
    ];

    // These are replaces by their respective @settings values.
    unset($form['scheme']);
    unset($form['port']);
    unset($form['host']);
    unset($form['core']);
    unset($form['path']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    try {
      $values = $this->generateConfiguration($values);

      // Try to orchestrate a server link from form values.
      $solr = new Client(NULL, $this->eventDispatcher);
      $solr->createEndpoint($values + ['key' => 'core'], TRUE);
      $this->getServerLink();
    }
    catch (SearchApiSolrException $e) {
      $form_state->setError($form['solr_instance'], Markup::create($e->getMessage()));
    }
    catch (\InvalidArgumentException $e) {
      $form_state->setError($form['solr_instance'], $this->t('The server link generated from the form values is illegal.'));
    }
  }

}
