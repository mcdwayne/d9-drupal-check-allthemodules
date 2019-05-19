<?php

namespace Drupal\taxonomy_breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

/**
 * {@inheritdoc}
 */
class TaxonomyBreadcrumb implements BreadcrumbBuilderInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The menu link manager interface.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The taxonomy storage.
   *
   * @var \Drupal\Taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * The menu active trail interface.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    MenuLinkManagerInterface $menu_link_manager,
    EntityManagerInterface $entityManager,
    MenuActiveTrailInterface $menu_active_trail
  ) {
    $this->config = $config_factory->getEditable('taxonomy_breadcrumb.settings');
    $this->menuLinkManager = $menu_link_manager;
    $this->entityManager = $entityManager;
    $this->termStorage = $entityManager->getStorage('taxonomy_term');
    $this->menuActiveTrail = $menu_active_trail;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    // This might be a "node" with no fields, e.g. a route to a "revision" URL,
    // so we don't check for taxonomy fields on unfieldable nodes:
    $node_object = $route_match->getParameters()->get('node');
    $node_is_fieldable = $node_object instanceof FieldableEntityInterface;
    if ($node_is_fieldable) {
      $bundle = $node_object->bundle();
      $node_types = $this->config->get('taxonomy_breadcrumb_node_types');
      $exclude_include = $this->config->get('taxonomy_breadcrumb_include_nodes');

      if ($exclude_include) {
        // Include option.
        if (!$node_types[$bundle]) {
          return FALSE;
        }
      }
      else {
        // Exclude option.
        if ($node_types[$bundle]) {
          return FALSE;
        }
      }
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $node_object = $route_match->getParameters()->get('node');
    $node_is_fieldable = $node_object instanceof FieldableEntityInterface;

    // Generate the HOME breadcrumb.
    if ($this->config->get('taxonomy_breadcrumb_home')) {
      $home = $this->config->get('taxonomy_breadcrumb_home');
      $breadcrumb->addLink(Link::createFromRoute($home, '<front>'));
    }

    // Generate the VOCABULARY breadcrumb.
    if ($node_is_fieldable) {

      // Check all taxonomy terms applying to the current page.
      foreach ($node_object->getFields() as $field) {

        if ($field->getSetting('target_type') == 'taxonomy_term') {
          $vocabulary_bundles = $field->getSettings()['handler_settings']['target_bundles'];
          // For now doing for nodes with single vocabulary as reference.
          $vocabulary_machine_name = reset($vocabulary_bundles);
          $entity_type = 'taxonomy_vocabulary';
          $vocabulary_label = $vocabulary_bundles[$vocabulary_machine_name];
          $vocabulary_entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($vocabulary_machine_name);
          $taxonomy_breadcrumb_path = isset($vocabulary_entity->getThirdPartySettings("taxonomy_breadcrumb", "taxonomy_breadcrumb_path")['taxonomy_breadcrumb_path']) ? $vocabulary_entity->getThirdPartySettings("taxonomy_breadcrumb", "taxonomy_breadcrumb_path")['taxonomy_breadcrumb_path'] : '';
          if ($taxonomy_breadcrumb_path) {
            $breadcrumb->addLink(Link::fromTextAndUrl($vocabulary_label, Url::fromUri('base:/' . $taxonomy_breadcrumb_path)));
          }

          // Generate the TERM breadcrumb.
          foreach ($field->referencedEntities() as $term) {
            if ($term->get('taxonomy_breadcrumb_path')->getValue()) {
              $breadcrumb->addLink(Link::fromTextAndUrl($term->get('taxonomy_breadcrumb_path')->getValue()[0]['title'], Url::fromUri($term->get('taxonomy_breadcrumb_path')->getValue()[0]['uri'])));
            }
            else {
              $breadcrumb->addLink(Link::createFromRoute($term->getName(), 'entity.taxonomy_term.canonical', ['taxonomy_term' => $term->id()]));
            }
          }
        }
      }
    }
    return $breadcrumb;
  }

}
