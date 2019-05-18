<?php

namespace Drupal\og_sm\Plugin\views\argument_validator;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\og_sm\SiteManagerInterface;
use Drupal\views\Plugin\views\argument_validator\ArgumentValidatorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument validator for site nodes.
 *
 * @ingroup views_argument_validate_plugins
 *
 * @ViewsArgumentValidator(
 *   id = "og_sm_site",
 *   title = @Translation("Site manager: Site")
 * )
 */
class Site extends ArgumentValidatorPluginBase implements ContainerFactoryPluginInterface {


  /**
   * The site manager.
   *
   * @var \Drupal\og_sm\SiteManagerInterface
   */
  protected $siteManager;

  /**
   * Constructs a new Site instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $pluginId
   *   The plugin_id for the plugin instance.
   * @param mixed $pluginDefinition
   *   The plugin implementation definition.
   * @param \Drupal\og_sm\SiteManagerInterface $siteManager
   *   The language manager.
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, SiteManagerInterface $siteManager) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->siteManager = $siteManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('og_sm.site_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validateArgument($argument) {
    if ($argument) {
      return (bool) $this->siteManager->load($argument);
    }
    return FALSE;
  }

}
