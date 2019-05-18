<?php

namespace Drupal\domain_access_logo\Form;

use Drupal\file\Entity\File;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\domain\DomainLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;

/**
 * DomainAccess Logo SettingsForm.
 */
class DomainAccessLogoSettingsForm extends ConfigFormBase {
  /**
   * The config object for the domain_logo settings.
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
    return new static($container->get('config.factory'), $container->get('domain.loader'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'domain_logo_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['domain_logo.settings'];
  }

  /**
   * Function for config domain logo form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('domain_logo.settings');
    $domains = $this->domainLoader->loadMultiple();

    if ($domains) {
      $form['general'] = [
        '#type' => 'details',
        '#title' => $this->t('Domain Logo Settings'),
        '#open' => TRUE,
      ];
      $configFactory = $this->config('domain_logo.settings');

      foreach ($domains as $domain) {
        $hostname = $domain->gethostname();
        if ($domain->id()) {
          $form['general'][$domain->id()] = [
            '#type' => 'managed_file',
            '#title' => $this->t('Upload logo for Domain: @hostname', ['@hostname' => $hostname]),
            '#description' => $this->t('The logo for this subdomain'),
            '#maxlength' => 64,
            '#size' => 64,
            '#default_value' => $configFactory->get($domain->id()),
            '#upload_location' => 'public://files',
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
   * Domain logo config form submit.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('domain_logo.settings');
    $domains = $this->domainLoader->loadOptionsList();
    foreach ($domains as $key => $value) {
      $config->set($key, $form_state->getValue($key))->save();
      $image = $form_state->getValue($key);
      $file = File::load($image[0]);
      if (!empty($file)) {
        $file->setPermanent();
        $file->save();
      }
    }
    parent::submitForm($form, $form_state);
  }

}
