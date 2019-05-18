<?php

namespace Drupal\pki_ra\Plugin\EoiProgress;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandler;

/**
 * EOI Sources progress manager.
 */
class EoiSourcesProgressManager implements ContainerInjectionInterface {

  protected $configFactory;

  protected $databaseConnection;

  protected $moduleHandler;

  /**
   * @param ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'), $container->get('module_handler'), $container->get('config.factory'));
  }

  /**
   * EoiSourcesProgressManager constructor.
   *
   * @param Connection $databaseConnection
   *
   * @param ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(Connection $databaseConnection, ModuleHandler $module_handler, ConfigFactoryInterface $config_factory) {
    $this->databaseConnection = $databaseConnection;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Update EOI Source progress.
   *
   * @param $registration
   * @param $timestamp
   */
  public function userEoiSourceProgress($data) {
    if ($this->databaseConnection->schema()->tableExists('pki_ra_eoi_progress')) {
      $query = $this->databaseConnection->merge('pki_ra_eoi_progress');
      $query->key(['registration_id' => $data['registration_id'], 'eoi_method' => $data['eoi_method']]);
      $query->fields($data);
      $query->execute();
    }
    return FALSE;
  }

  /**
   *  Get user EOI Sources progress.
   *
   * @param $registration_id
   * @return mixed
   */
  public function getEoiSourcesProgress($registration_id, $method = NULL) {
    if ($this->databaseConnection->schema()->tableExists('pki_ra_eoi_progress')) {
      $query = $this->databaseConnection->select('pki_ra_eoi_progress', 'pr');
      $query->fields('pr');
      $query->condition('pr.registration_id', $registration_id);
      if ($method) {
        $query->condition('pr.eoi_method', $method);
      }
      $result = $query->execute()->fetchObject();
      return $result;
    }
    return FALSE;
  }

  /**
   * Provide EOI Sources.
   *
   * @return array
   *   EOI Sources.
   */
  public function availableEoiSources() {
    $config = $this->configFactory->get('pki_ra.settings');
    $order = $config->get('eoi_sources.order');
    $sources = [
      'email' => [
        'weight' => 1,
        'label' => t('Email Verification'),
        'url' => Url::fromRoute('pki_ra.registration.start')->toString(),
        'options' => [
          'required' => t('Required'),
          'enabled' => t('Enabled'),
          'disabled' => t('Disabled'),
        ],
      ],
      'certificate' => [
        'weight' => 2,
        'label' => t('Certificate'),
        'url' => NULL,
        'options' => [
          'required' => t('Required'),
          'enabled' => t('Enabled'),
          'disabled' => t('Disabled'),
        ],
      ],
    ];
    // Allow modules to add more EOI Sources.
    $this->moduleHandler->alter('pki_ra_eoi_sources', $sources);
    $sort_methods = $sources;
    // Sort methods.
    if (!empty($config->get('eoi_sources.order'))) {
      $sort_methods = array_replace(array_flip($order), $sources);
    }
    return $sort_methods;
  }

  /**
   * Get enabled or optional EOI Sources.
   *
   * @return array
   *   An array of enabled or optional EOI Sources.
   */
  public function getEnabledEoiSources() {
    $config = $this->configFactory->get('pki_ra.settings');
    $methods = $config->get('eoi_sources.order');
    $eoi_sources = $this->availableEoiSources();
    $active_methods = [];
    foreach ($methods as $method) {
      $status = $config->get('eoi_sources.' . $method . '.status');
      if ($status != 'disabled' && !empty($eoi_sources[$method]['label'])) {
        $active_methods[$method] = $eoi_sources[$method];
      }
    }
    return $active_methods;
  }

}
