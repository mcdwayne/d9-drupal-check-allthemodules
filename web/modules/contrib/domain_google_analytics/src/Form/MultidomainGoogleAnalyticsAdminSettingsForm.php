<?php

namespace Drupal\multidomain_google_analytics\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\domain\DomainLoader;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;

/**
 * Configure Google_Analytics settings for this site.
 */
class MultidomainGoogleAnalyticsAdminSettingsForm extends ConfigFormBase {

  /**
   * The config object for the multidomain_google_analytics settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Drupal\domain\DomainLoader definition.
   *
   * @var \Drupal\domain\DomainLoader
   */
  protected $domainLoader;

  /**
   * Construct function.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\domain\DomainLoader $domain_loader
   *   Load the domain records.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DomainLoader $domain_loader) {
    parent::__construct($config_factory);

    $this->domainLoader = $domain_loader;
  }

  /**
   * Create function return static domain loader configuration.
   *
   * @param Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Load the ContainerInterface.
   *
   * @return \static
   *   return domain loader configuration.
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('config.factory'), $container->get('domain.loader')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'multidomain_google_analytics_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['multidomain_google_analytics.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $domains = $this->domainLoader->loadMultiple();
    if ($domains) {
      $form['general'] = [
        '#type' => 'details',
        '#title' => $this->t('Multidomain Google Analytics Settings'),
        '#open' => TRUE,
      ];
      $configFactory = $this->config('multidomain_google_analytics.settings');

      foreach ($domains as $domain) {
        $hostname = $domain->gethostname();
        if ($domain->id()) {
          $form['general'][$domain->id()] = [
            '#type' => 'textfield',
            '#title' => $this->t('Google Analytics ID for Domain: @hostname', ['@hostname' => $hostname]),
            '#description' => $this->t('The ID assigned by Google Analytics for this website container.'),
            '#maxlength' => 64,
            '#size' => 64,
            '#default_value' => $configFactory->get($domain->id()),
            '#weight' => '0',
          ];
        }
      }
      return parent::buildForm($form, $form_state);
    }
    else {
      $url = Url::fromRoute('domain.admin');
      $domain_link = $this->l($this->t('Domain records'), $url);
      $form['title']['#markup'] = $this->t('There is no Domain record yet.Please create a domain records.See link: @domain_list', ['@domain_list' => $domain_link]);
      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('multidomain_google_analytics.settings');
    $domains = $this->domainLoader->loadOptionsList();
    foreach ($domains as $key => $value) {
      $config->set($key, $form_state->getValue($key))->save();
    }

    parent::submitForm($form, $form_state);
  }

}
