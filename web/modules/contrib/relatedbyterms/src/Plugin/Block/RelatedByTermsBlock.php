<?php

namespace Drupal\relatedbyterms\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Component\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\relatedbyterms\RelatedByTermsServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a Related content Block.
 *
 * @Block(
 *   id = "relatedbyterms_block",
 *   admin_label = @Translation("Related by Terms block"),
 *   category = @Translation("Related by Terms"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   }
 * )
 */
class RelatedByTermsBlock extends BlockBase implements ContextAwarePluginInterface, ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Related By Terms service.
   *
   * @var \Drupal\Core\Entity\RelatedByTermsServiceInterface
   */
  protected $relatedbytermsManager;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\RelatedByTermsServiceInterface $relatedbyterms_manager
   *   The Related By Terms manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, RelatedByTermsServiceInterface $relatedbyterms_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->relatedbytermsManager = $relatedbyterms_manager;

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('relatedbyterms.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->getContents();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $base_config = $this->baseConfigurationDefaults();
    $base_config['label'] = $this->relatedbytermsManager->getDefaultTitle();

    return $base_config;
  }

  /**
   * Returns block contents.
   */
  public function getContents() {
    $current_node = $this->getContextValue('node');
    $display_mode = $this->relatedbytermsManager->getDisplayMode();
    $nids = $this->relatedbytermsManager->getRelatedNodes($current_node->id());

    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
    $build = $this->entityTypeManager->getViewBuilder('node')->viewMultiple($nodes, $display_mode);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($account->hasPermission('access content')) {
      return AccessResult::allowed();
    }
    else {
      return AccessResult::forbidden();
    }
  }

}
