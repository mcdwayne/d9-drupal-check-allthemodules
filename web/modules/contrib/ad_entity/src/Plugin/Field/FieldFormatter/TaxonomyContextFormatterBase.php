<?php

namespace Drupal\ad_entity\Plugin\Field\FieldFormatter;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\TermStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ad_entity\Plugin\AdContextManager;

/**
 * Base class for Context formatters using Taxonomy terms.
 */
abstract class TaxonomyContextFormatterBase extends ContextFormatterBase {

  /**
   * The term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * An array of known node terms, keyed by nid.
   *
   * @var array
   */
  protected $nodeTerms;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('ad_entity.context_manager'),
      $container->get('module_handler'),
      $container->get('renderer'),
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('taxonomy_term')
    );
    $global_settings = $container->get('config.factory')->get('ad_entity.settings');
    $instance->setGlobalSettings($global_settings);
    return $instance;
  }

  /**
   * Constructs a new TaxonomyContextFormatterBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\ad_entity\Plugin\AdContextManager $context_manager
   *   The Advertising context manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\taxonomy\TermStorageInterface $term_storage
   *   The term storage.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AdContextManager $context_manager, ModuleHandlerInterface $module_handler, Renderer $renderer, AccountInterface $current_user, TermStorageInterface $term_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $context_manager, $module_handler, $renderer, $current_user);
    $this->termStorage = $term_storage;
    $this->nodeTerms = [];
  }

  /**
   * Returns the first non-empty list in the term's ancestor tree (bottom-up).
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The given item list of the current term.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *   The list as object when items were found, or an empty item list.
   */
  protected function getOverrideItems(FieldItemListInterface $items) {
    $term = $items->getEntity();
    $accessible = $term->access('view', $this->currentUser);
    if (!$items->isEmpty() && $accessible) {
      return $items;
    }

    $field_name = $items->getFieldDefinition()->get('field_name');
    $parents = $this->termStorage->loadParents($term->id());
    foreach ($parents as $parent) {
      $parent_items = $this->getOverrideItems($parent->get($field_name));
      if (!$parent_items->isEmpty()) {
        return $parent_items;
      }
    }

    return $items;
  }

  /**
   * Get the terms of a node.
   *
   * @param int $nid
   *   The node id.
   *
   * @return array
   *   The list of terms which belong to the node.
   */
  protected function getTermsForNode($nid) {
    if (!isset($this->nodeTerms[$nid])) {
      $node_terms = $this->termStorage->getNodeTerms([$nid]);
      $this->nodeTerms[$nid] = !empty($node_terms[$nid]) ? $node_terms[$nid] : [];
    }
    return $this->nodeTerms[$nid];
  }

}
