<?php

namespace Drupal\micro_taxonomy\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\micro_site\SiteNegotiatorInterface;

/**
 * Derivative class that provides the menu links for the Products.
 */
class ViewsAddLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager.
   *
   * The entity type manager service.
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Creates a MicroPageAddLocalTasks instance.
   *
   * @param $base_plugin_id
   *   The base plugin id.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\micro_site\SiteNegotiatorInterface $site_negotiator
   *   The site negotiator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct($base_plugin_id, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, SiteNegotiatorInterface $site_negotiator, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->negotiator = $site_negotiator;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('micro_site.negotiator'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (!$this->moduleHandler->moduleExists('views')) {
      return $this->derivatives;
    }

    $tab_id = 'view.site_taxonomy_term.tab';
    $tab_all = 'view.site_taxonomy_term.tab_all';

    $this->derivatives[$tab_id] = $base_plugin_definition;
    $this->derivatives[$tab_id]['title'] = 'Terms in common vocabularies';
    $this->derivatives[$tab_id]['route_name'] =  $tab_id;
    $this->derivatives[$tab_id]['routes_parameters'] =  ['taxonomy_vocabulary' => 'fake_site_vocabulary'];
    $this->derivatives[$tab_id]['class'] =  '\Drupal\micro_taxonomy\LocalTask\SiteTaxonomyLocalTask';

    $this->derivatives[$tab_all] = $base_plugin_definition;
    $this->derivatives[$tab_all]['title'] = 'Terms available on all sites';
    $this->derivatives[$tab_all]['route_name'] =  $tab_all;
    $this->derivatives[$tab_all]['routes_parameters'] =  ['taxonomy_vocabulary' => 'fake_site_vocabulary'];
    $this->derivatives[$tab_all]['class'] =  '\Drupal\micro_taxonomy\LocalTask\SiteTaxonomyLocalTask';

    return $this->derivatives;
  }
}