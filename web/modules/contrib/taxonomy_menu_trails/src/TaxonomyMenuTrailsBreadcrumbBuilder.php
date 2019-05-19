<?php

namespace Drupal\taxonomy_menu_trails;


use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * {@inheritdoc}
 */
class TaxonomyMenuTrailsBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use \Drupal\Core\StringTranslation\StringTranslationTrait;

  /**
   * The configuration object generator.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

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
    $this->configFactory = $config_factory;
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
      $entity_type = 'node';
      $type = \Drupal::service('entity.manager')->getStorage('node_type')->load($bundle);
      // Check if node type is selected in content type taxonomy trails settings.
      // Check all the settings from content type configuration form.
      $configurations = $type->getThirdPartySetting('taxonomy_menu_trails', 'taxonomy_menu_trails');
      switch ($configurations['set_breadcrumb']) {
        case "never":
          return FALSE;
        case 'if_empty':
          $trailIds = $this->menuActiveTrail->getActiveTrailIds('main');
          if(!$trailIds) {
            return FALSE;
          }
          break;
        case 'always':
          break;
      }
      foreach (\Drupal::entityManager()->getFieldDefinitions($entity_type, $bundle) as $field_name => $field_definition) {
        // Look for a "taxonomy attachment" by node field, regardless of language.
        if ($configurations['taxonomy_term_references'][$field_name]) {
          if (!empty($field_definition->getTargetBundle())) {
            // Check for term_reference/entity_reference fields from the content type.
            if ($field_definition->getType() == 'entity_reference') {
              // Check all taxonomy terms applying to the current page.
              foreach ($node_object->getFields() as $field) {
                if ($field->getSetting('target_type') == 'taxonomy_term') {
                  return TRUE;
                }
              }
            }

          }
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
    $node_object = $route_match->getParameters()->get('node');
    $node_is_fieldable = $node_object instanceof FieldableEntityInterface;

    if ($node_is_fieldable) {
      // Check all taxonomy terms applying to the current page.
      foreach ($node_object->getFields() as $field) {
        if ($field->getSetting('target_type') == 'taxonomy_term') {
          foreach ($field->referencedEntities() as $term) {
            $url = $term->toUrl();$route_links = $this->menuLinkManager->loadLinksByRoute($url->getRouteName(), $url->getRouteParameters());
            if (!empty($route_links)) {
              $taxonomy_term_link = reset($route_links);
              $plugin_definition = $taxonomy_term_link->getPluginDefinition();
              $taxonomy_term_id = $plugin_definition['route_parameters']['taxonomy_term'];
              $parents = $this->termStorage->loadAllParents($taxonomy_term_id);
              foreach (array_reverse($parents) as $term1) {
                $term1 = $this->entityManager->getTranslationFromContext($term1);
                $breadcrumb->addLink(Link::createFromRoute($term1->getName(), 'entity.taxonomy_term.canonical', ['taxonomy_term' => $term1->id()]));
              }
            }
          }
        }
      }
    }
    return $breadcrumb;
  }

}
