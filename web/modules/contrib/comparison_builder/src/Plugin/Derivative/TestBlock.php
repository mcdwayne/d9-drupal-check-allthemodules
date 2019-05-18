<?php
/**
 * @file
 * Contains \Drupal\comparison_builder\Plugin\Derivative\TestBlock.
 */
namespace Drupal\comparison_builder\Plugin\Derivative;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
/**
 * Provides block plugin definitions for nodes.
 *
 * @see \Drupal\comparison_builder\Plugin\Block\TestBlock
 */
class TestBlock extends DeriverBase implements ContainerDeriverInterface {
  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;
  /**
   * Creates a new NodeBlock.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_storage
   *   The node storage.
   */
  public function __construct(EntityStorageInterface $node_storage) {
    $this->nodeStorage = $node_storage;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager')->getStorage('node')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    
    $query_content_type = db_query("SELECT * FROM config WHERE name LIKE 'node.type.%'");
    $redult_content_type = $query_content_type->fetchAll();

    foreach ($redult_content_type as $content_type_value) {
      $this->derivatives[$content_type_value->name] = $base_plugin_definition;
      $this->derivatives[$content_type_value->name]['admin_label'] = str_replace('node.type.', '', $content_type_value->name);
      $this->derivatives[$content_type_value->name]['cache'] = DRUPAL_NO_CACHE;
    }
    return $this->derivatives;
  }
}