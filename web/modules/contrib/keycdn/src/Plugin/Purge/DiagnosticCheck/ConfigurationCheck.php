<?php

namespace Drupal\keycdn\Plugin\Purge\DiagnosticCheck;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\keycdn\Entity\KeyCDNPurgerSettings;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckBase;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;
use Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Verifies that only fully configured Key CDN purgers load.
 *
 * @PurgeDiagnosticCheck(
 *   id = "purge_purger_keycdn",
 *   title = @Translation("Key CDN "),
 *   description = @Translation("Tests your Key CDN configuration."),
 * )
 */
class ConfigurationCheck extends DiagnosticCheckBase implements DiagnosticCheckInterface {

  /**
  * The purgers service.
  *
  * @var \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface
  */
  protected $purgePurgers;

  /**
   * Constructs an instance of ConfigurationCheck.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface $purge_purgers
   *   The purge executive service, which wipes content from external caches.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PurgersServiceInterface $purge_purgers) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->purgePurgers = $purge_purgers;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('purge.purgers')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    // Load configuration objects for all enabled HTTP purgers.
    $plugins = [];
    foreach ($this->purgePurgers->getPluginsEnabled() as $id => $plugin_id) {
      if (in_array($plugin_id, ['purge_purger_keycdn'])) {
        $plugins[$id] = KeyCDNPurgerSettings::load($id);
      }
    }

    // Perform checks against configuration.
    $labels  = $this->purgePurgers->getLabels();
    foreach ($plugins as $id => $settings) {
      $t = ['@purger' => $labels[$id]];
      foreach (['zone', 'api_key'] as $f) {
        if (empty($settings->get($f))) {
          $this->recommendation = $this->t("@purger not configured.", $t);
          return SELF::SEVERITY_ERROR;
        }
      }
    }

    $this->recommendation = "All purgers configured.";
    return SELF::SEVERITY_OK;
  }
}
