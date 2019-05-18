<?php

namespace Drupal\packages\Plugin\views\access;

use Drupal\packages\PackagesInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides package-based access control.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "package",
 *   title = @Translation("Package"),
 *   help = @Translation("Access will be granted to users with the specified package active.")
 * )
 */
class Package extends AccessPluginBase implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = TRUE;

  /**
   * The packages service.
   *
   * @var \Drupal\packages\PackagesInterface
   */
  protected $packages;

  /**
   * Constructs a Package object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\packages\PackagesInterface $packages
   *   The packages service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PackagesInterface $packages) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->packages = $packages;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('packages')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $this->packages->getState($this->options['package'])->isActive();
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_package', $this->options['package']);
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    if (!empty($this->options['package'])) {
      return $this->packages
        ->getPackage($this->options['package'])
        ->getPluginDefinition()['label'];
    }
    return $this->t('(Missing package)');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['package'] = ['default' => NULL];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Add the option to select the package.
    $form['package'] = [
      '#type' => 'select',
      '#options' => [],
      '#title' => $this->t('Package'),
      '#default_value' => $this->options['package'],
      '#description' => $this->t('Only users with the selected package enabled and accessible will be able to access this display.'),
      '#required' => TRUE,
    ];

    // Get list of packages.
    foreach ($this->packages->getPackageDefinitions() as $package_id => $definition) {
      $form['package']['#options'][$package_id] = $definition['label'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

}
