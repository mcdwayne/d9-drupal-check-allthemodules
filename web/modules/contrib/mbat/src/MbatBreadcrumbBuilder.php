<?php

namespace Drupal\mbat;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkManager;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

/**
 * {@inheritdoc}
 */
class MbatBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use \Drupal\Core\StringTranslation\StringTranslationTrait;

  /**
   * The menu active trail interface.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * The menu link manager interface.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * The configuration object generator.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The admin context generator.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * The menu where the current page or taxonomy match has taken place.
   *
   * @var string
   */
  private $menuName;

  /**
   * The menu trail leading to this match.
   *
   * @var string
   */
  private $menuTrail;

  /**
   * Node of current path if taxonomy attached.
   *
   * @var string
   */
  private $taxonomyAttachment;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    MenuActiveTrailInterface $menu_active_trail,
    MenuLinkManager $menu_link_manager,
    ConfigFactory $config_factory,
    AdminContext $admin_context
  ) {
    $this->menuActiveTrail = $menu_active_trail;
    $this->menuLinkManager = $menu_link_manager;
    $this->configFactory = $config_factory;
    $this->adminContext = $admin_context;
    $this->config = $this->configFactory->get('mbat.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    // This may look heavyweight for applies() but we have to check all ways the
    // current path could be attached to the selected menus before turning over
    // breadcrumb building (and caching) to another builder.  Generally this
    // should not be a problem since it will then fall back to the system (path
    // based) breadcrumb builder which caches a breadcrumb no matter what.
    if (!$this->config->get('module_enabled')) {
      // NOTE this option is called "determine_menu" in menu_breadcrumb module.
      return FALSE;
    }
    // Don't breadcrumb the admin pages, if disabled on config options:
    if ($this->config->get('disable_admin_page') && $this->adminContext->isAdminRoute($route_match->getRouteObject())) {
      return FALSE;
    }
    // No route name means no active trail:
    $route_name = $route_match->getRouteName();
    if (!$route_name) {
      return FALSE;
    }

    // This might be a "node" with no fields, e.g. a route to a "revision" URL,
    // so we don't check for taxonomy fields on unfieldable nodes:
    $node_object = $route_match->getParameters()->get('node');
    $node_is_fieldable = $node_object instanceof FieldableEntityInterface;

    // Check each selected menu, in turn, until a menu or taxonomy match found:
    // then cache its state for building & caching in build() and exit.
    $menus = $this->config->get('mbat_menus');
    uasort($menus, function ($a, $b) {
      return SortArray::sortByWeightElement($a, $b);
    });
    foreach ($menus as $menu_name => $params) {

      // Look for current path on any enabled menu.
      if (!empty($params['enabled'])) {

        $trailIds = $this->menuActiveTrail->getActiveTrailIds($menu_name);
        $trailIds = array_filter($trailIds);
        if ($trailIds) {
          $this->menuName = $menu_name;
          $this->menuTrail = $trailIds;
          $this->taxonomyAttachment = NULL;
          return TRUE;
        }
      }

      // Look for a "taxonomy attachment" by node field.
      if (!empty($params['taxattach']) && $node_is_fieldable) {

        // Check all taxonomy terms applying to the current page.
        foreach ($node_object->getFields() as $field) {
          if ($field->getSetting('target_type') == 'taxonomy_term') {

            // In general these entity references will support multiple
            // values so we check all terms in the order they are listed.
            foreach ($field->referencedEntities() as $term) {
              $url = $term->toUrl();
              $route_links = $this->menuLinkManager->loadLinksByRoute($url->getRouteName(), $url->getRouteParameters(), $menu_name);
              if (!empty($route_links)) {
                // Successfully found taxonomy attachment, so pass to build():
                // - the menu in in which we have found the attachment
                // - the effective menu trail of the taxonomy-attached node
                // - the node itself (in build() we will find its title & URL)
                $taxonomy_term_link = reset($route_links);
                $taxonomy_term_id = $taxonomy_term_link->getPluginId();
                $this->menuName = $menu_name;
                $this->menuTrail = $this->menuLinkManager->getParentIds($taxonomy_term_id);
                $this->taxonomyAttachment = $node_object;
                return TRUE;
              }
            }
          }
        }
      }
    }
    // No more menus to check...
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {

    $breadcrumb = new Breadcrumb();
    // Breadcrumbs accumulate in this array, with lowest index being the root
    // (i.e., the reverse of the assigned breadcrumb trail):
    $links = [];

    // Changing the <front> page will invalidate any breadcrumb generated here:
    $site_config = $this->configFactory->get('system.site');
    $breadcrumb->addCacheableDependency($site_config);

    // Changing any module settings will invalidate the breadcrumb:
    $breadcrumb->addCacheableDependency($this->config);

    // Changing the active trail of the current path, or taxonomy-attached path,
    // on this menu will invalidate this breadcrumb:
    $breadcrumb->addCacheContexts(['route.menu_active_trails:' . $this->menuName]);

    // Generate basic breadcrumb trail from active trail.
    // Keep same link ordering as Menu Breadcrumb (so also reverses menu trail)
    foreach (array_reverse($this->menuTrail) as $id) {
      $def = $this->menuLinkManager->getDefinition($id);
      $def_route_name = $def['route_name'];
      if ($def_route_name) {
        $url_object = Url::fromRoute($def_route_name, $def['route_parameters']);
      }
      else {
        // If no route, it's an external URI (issue 2750821):
        $url_object = Url::fromUri($def['url']);
      }
      $links[] = Link::fromTextAndUrl($def['title'], $url_object);
    }

    // Create a breadcrumb for <front> which may be either added or replaced:
    $label = $this->config->get('home_as_site_name') ?
      $this->configFactory->get('system.site')->get('name') :
      $this->t('Home');
    $home_link = Link::createFromRoute($label, '<front>');

    // The first link from the menu trail, being the root, may be the
    // <front> so first compare those two routes to see if they are identical.
    // (Though in general a link deeper in the menu could be <front>, in that
    // case it's arguable that the node-based pathname would be preferred.)
    $front_page = $site_config->get('page.front');
    $front_url = Url::fromUri("internal:$front_page");
    $first_url = $links[0]->getUrl();
    // If options are set to remove <front>, strip off that link, otherwise
    // replace it with a breadcrumb named according to option settings:
    if (($front_url->getRouteName() === $first_url->getRouteName()) && ($front_url->getRouteParameters() === $first_url->getRouteParameters())) {

      // According to the confusion hopefully cleared up in issue 2754521, the
      // sense of "remove_home" is slightly different than in Menu Breadcrumb:
      // we remove any match with <front> rather than replacing it.
      if ($this->config->get('remove_home')) {
        array_shift($links);
      }
      else {
        $links[0] = $home_link;
      }
    }
    else {
      // If trail *doesn't* begin with the home page, add it if that option set.
      if ($this->config->get('add_home')) {
        array_unshift($links, $home_link);
      }
    }

    // If there's been a taxonomy attachment, attach a link for the node itself
    // as required by the option settings.  This leaves the active trail of the
    // attached node fully breadcrumbed, regardless of "current_page" options.
    if ($this->config->get('append_member_page') && $this->taxonomyAttachment) {

      $current_title = $this->taxonomyAttachment->getTitle();
      $current_url = Url::fromRoute($route_match->getRouteName(), $route_match->getRawParameters()->all());
      if ($this->config->get('member_page_as_link')) {
        $links[] = Link::fromTextAndUrl($current_title, $current_url);
      }
      else {
        $links[] = Link::createFromRoute($current_title, '<none>');
      }

    }
    else {
      // Display the last item of the breadcrumbs trail as the options indicate,
      // with a link for the last breadcrumb when there's a taxonomy attachment.
      if (!empty($links)) {
        /** @var \Drupal\Core\Link $current */
        $current = array_pop($links);
        if ($this->config->get('append_current_page')) {
          if (!$this->config->get('current_page_as_link') && !$this->taxonomyAttachment) {
            $current->setUrl(new Url('<none>'));
          }
          array_push($links, $current);
        }
      }
    }

    return $breadcrumb->setLinks($links);
  }

}
