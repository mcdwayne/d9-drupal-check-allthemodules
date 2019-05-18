<?php

namespace Drupal\og_sm\Plugin\views\filter;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\og_sm\SiteManagerInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\BooleanOperator;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter entities based on the current user's manageable sites.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("og_sm_manageable_sites_by_current_user")
 */
class ManageableSitesByCurrentUser extends BooleanOperator implements ContainerFactoryPluginInterface {

  /**
   * The site manager.
   *
   * @var \Drupal\og_sm\SiteManagerInterface
   */
  protected $siteManager;

  /**
   * Constructs a new SitesFilter instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $pluginId
   *   The plugin_id for the plugin instance.
   * @param mixed $pluginDefinition
   *   The plugin implementation definition.
   * @param \Drupal\og_sm\SiteManagerInterface $siteManager
   *   The site manager.
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
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->value_value = $this->t('Are the sites manageable by the current user');
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $field = "$this->tableAlias.$this->realField";

    $sites = $this->siteManager->getUserManageableSites();
    $siteIds = array_keys($sites);
    if (empty($this->value)) {
      $this->query->addWhere(0, $field, $siteIds, 'NOT IN');
    }
    else {
      $this->query->addWhere(0, $field, $siteIds, 'IN');
    }
  }

}
