<?php

namespace Drupal\block_placeholder\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\ConfigEntityType;
use Drupal\Core\Entity\EntityInterface;

/**
 * Define block placeholder reference entity config.
 *
 * @ConfigEntityType(
 *   id = "block_placeholder",
 *   label = @Translation("Block placeholder"),
 *   config_prefix = "placeholder_reference",
 *   admin_permission = "administer block placeholder reference",
 *   handlers = {
 *     "form" = {
 *       "add" = "\Drupal\block_placeholder\Form\BlockPlaceholderForm",
 *       "edit" = "\Drupal\block_placeholder\Form\BlockPlaceholderForm",
 *       "delete" = "\Drupal\block_placeholder\Form\BlockPlaceholderDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "\Drupal\block_placeholder\Entity\Routing\BlockPlaceholderRoutingProvider"
 *     },
 *     "list_builder" = "\Drupal\block_placeholder\Controller\BlockPlaceholderListBuilder",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "collection" = "/admin/structure/block/block-placeholder",
 *     "add-form" = "/admin/structure/block/block-placeholder/add",
 *     "edit-form" = "/admin/structure/block/block-placeholder/{block_placeholder}",
 *     "delete-form" = "/admin/structure/block/block-placeholder/{block_placeholder}/delete"
 *   }
 * )
 */
class BlockPlaceholderReference extends ConfigEntityBase implements BlockPlaceholderInterface {

  /**
   * Block placeholder identifier.
   *
   * @var string
   */
  protected $id;

  /**
   * Block placeholder label.
   *
   * @var string
   */
  protected $label;

  /**
   * Block placeholder block types.
   *
   * @var array
   */
  protected $block_types = [];

  /**
   * Block placeholder referenced limit type.
   *
   * @var string
   */
  protected $reference_limit_type = 'unlimited';

  /**
   * Block placeholder referenced limited value.
   *
   * @var integer
   */
  protected $reference_limited_value = 1;

  /**
   * {@inheritdoc}
   */
  public function blockTypes() {
    return $this->block_types;
  }

  /**
   * {@inheritdoc}
   */
  public function referenceLimitType() {
    return $this->reference_limit_type;
  }

  /**
   * {@inheritdoc}
   */
  public function referencedLimitedValue() {
    return $this->reference_limited_value;
  }

  /**
   * {@inheritdoc}
   */
  public function loadReferences(array $exclude_ids = []) {
    $storage = $this
      ->entityTypeManager()
      ->getStorage('block_content');

    $query = $storage->getQuery();
    $query->condition('block_placeholder', $this->id());

    if (!empty($exclude_ids)) {
      $query->condition('id', array_filter($exclude_ids), 'NOT IN');
    }
    $block_ids = $query->execute();

    return $storage->loadMultiple($block_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function hasReferenceMetLimit(array $exclude_ids = []) {
    if ($this->referenceLimitType() === 'unlimited') {
      return FALSE;
    }
    $references = $this->loadReferences($exclude_ids);
    $current_count = count($references);

    if ($current_count < $this->referencedLimitedValue()
      && $current_count !== $this->referencedLimitedValue()) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function invalidBlockTypes() {
    $invalided_types = [];

    foreach ($this->loadReferences() as $reference) {
      if (!$reference instanceof EntityInterface) {
        continue;
      }
      $bundle = $reference->bundle();

      if (!in_array($bundle, $this->blockTypes())) {
        $invalided_types[] = $bundle;
      }
    }

    return $invalided_types;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlaceholderBlocks() {
    $blocks = [];

    foreach ($this->loadBlockPlaceholderBlocks() as $block_id => $block) {
      $config = $block->getPlugin()->getConfiguration();
      if (!isset($config['block_placeholder'])) {
        continue;
      }
      $placeholder_id = $config['block_placeholder'];

      if ($this->id() !== $placeholder_id) {
        continue;
      }

      $blocks[$block_id] = [
        'label' => $block->label()
      ];
    }

    return $blocks;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceCount() {
    return count($this->loadReferences());
  }

  /**
   * {@inheritdoc}
   */
  public function entityExist($id) {
    return (bool) $this->getQuery()
      ->condition('id', $id)
      ->execute();
  }

  /**
   * Load block placeholder blocks.
   *
   * @return EntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function loadBlockPlaceholderBlocks() {
    return $this->entityTypeManager()
      ->getStorage('block')
      ->loadByProperties(['plugin' => 'block_placeholder']);
  }

  /**
   * Get entity query.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getQuery() {
    return $this->getStorage()->getQuery();
  }

  /**
   * Get entity storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getStorage() {
    return $this
      ->entityTypeManager()
      ->getStorage($this->getEntityTypeId());
  }

  /**
   * Entity field manager.
   *
   * @return EntityFieldManagerInterface
   */
  protected function entityFieldManager() {
    return \Drupal::service('entity_field.manager');
  }
}
