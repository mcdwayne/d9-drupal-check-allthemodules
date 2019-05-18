<?php

namespace Drupal\og_sm\Entity\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection as DefaultSelectionBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og_sm\SiteManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Selection plugin for entity reference fields in site context.
 *
 * @EntityReferenceSelection(
 *   id = "og_sm",
 *   label = @Translation("Site manager"),
 *   group = "og_sm",
 *   weight = 5,
 *   deriver = "Drupal\Core\Entity\Plugin\Derivative\DefaultSelectionDeriver"
 * )
 */
class DefaultSelection extends DefaultSelectionBase {

  /**
   * The site manager.
   *
   * @var \Drupal\og_sm\SiteManagerInterface
   */
  protected $siteManager;

  /**
   * Constructs a NodeSelection object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\og_sm\SiteManagerInterface $site_manager
   *   The site manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user, SiteManagerInterface $site_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager, $module_handler, $current_user);
    $this->siteManager = $site_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('og_sm.site_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    $site = $this->siteManager->currentSite();
    if ($site) {
      $query->condition(OgGroupAudienceHelperInterface::DEFAULT_FIELD, $site->id());
    }

    return $query;
  }

}
