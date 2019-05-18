<?php

namespace Drupal\cache_tools\Plugin\views\cache;

use Drupal\cache_tools\Service\CacheSanitizer;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Simple caching of query results for Views displays.
 *
 * Module is NOT auto invalidating content tagged by such a cache tag. Module
 * cannot know how the field you choose will behave and how/when it should be
 * invalidated. Module is only capable of placing such a tag.
 *
 * @ingroup views_cache_plugins
 *
 * @ViewsCache(
 *   id = "cache_tools_sanitized_cache_field_tag",
 *   title = @Translation("Sanitized cache field tag"),
 *   help = @Translation("Tag based cache with sanitized tags by field")
 * )
 */
class SanitizedCacheFieldTag extends SanitizedCacheTag {

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = TRUE;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $fieldManager;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, CacheSanitizer $cacheSanitizer, EntityTypeManager $entityTypeManager, EntityFieldManager $fieldManager, RouteMatchInterface $routeMatch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $cacheSanitizer);
    $this->entityTypeManager = $entityTypeManager;
    $this->fieldManager = $fieldManager;
    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('cache_tools.cache.sanitizer'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function createTag($entityType, $bundle) {
    $tag = parent::createTag($entityType, $bundle);
    if (!empty($this->options['field'])) {
      $tag .= ':' . $this->options['field'];
      // Get argument handlers and their configuration.
      $args = $this->view->getHandlers('argument');
      $arg_index = 0;
      foreach ($args as $arg) {
        // Check if argument relationship matches the dependant field.
        if (isset($arg['relationship']) && $arg['relationship'] == $this->options['field']) {
          // Check if such an argument was provided.
          if (isset($this->view->args[$arg_index])) {
            return $tag . ':' . $this->view->args[$arg_index];
          }
        }
        $arg_index++;
      }
    }
    // If tag was not fully constructed do not set any, since it would be
    // too general and would cause too many unwanted cache invalidations.
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $options = [];

    /** @var \Drupal\node\Entity\NodeType[] $bundles */
    $bundles = $this->entityTypeManager
      ->getStorage('node_type')
      ->loadMultiple();
    foreach ($bundles as $bundleEntity) {
      $bundle = $bundleEntity->id();
      foreach ($this->fieldManager->getFieldDefinitions('node', $bundle) as $field_name => $field_definition) {
        if (!empty($field_definition->getTargetBundle())) {
          $options[$bundleEntity->label()][$field_name] = $field_definition->getLabel();
        }
      }
    }

    $form['field'] = [
      '#type' => 'select',
      '#title' => $this->t('Field'),
      '#description' => $this->t('The field to be set as cache tag. If the selected field occurs in other bundles, will be applied as well. <br /><strong>Note</strong>: This will only place the cache tag, which is not automatically invalidated. Invalidation for such a field based cache tag needs to be invalidated in a custom module. Follow README to get more information.'),
      '#options' => $options,
      '#default_value' => $this->options['field'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    return 'Field: ' . $this->options['field'];
  }

}
