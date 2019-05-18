<?php

namespace Drupal\rel_content\Plugin\RelatedContent;

use Drupal\group\Entity\GroupContentType;
use Drupal\plugin_type_example\SandwichBase;
use Drupal\rel_content\Annotation\RelatedContent;
use Drupal\rel_content\Plugin\views\filter\RelatedContentFilter;
use Drupal\rel_content\RelatedContentBase;
use Drupal\views\Views;

/**
 * Provides a group related content plugin.
 *
 * @RelatedContent(
 *   id = "group_rel_content",
 *   description = @Translation("Related content by group.")
 * )
 */
class GroupRelatedContent extends RelatedContentBase {

  /**
   * @inheritdoc
   */
  public function getOptions() {
    $options = [];

    foreach ($this->configuration['items'] as $item) {
      $group_content_types = GroupContentType::loadByContentPluginId('group_' . $item->getFieldDefinition()->getTargetEntityTypeId() . ':' . $item->getFieldDefinition()->getTargetBundle());
      foreach ($group_content_types as $group_content_type) {
        $options[$group_content_type->id()] = 'Group: ' . ucfirst($group_content_type->getGroupTypeId());
      }
    }

    return $options;
  }

  /**
   * @inheritdoc
   */
  public function viewsAlteration(RelatedContentFilter &$data) {
    $current_gid = \Drupal::entityTypeManager()
      ->getStorage('group_content')
      ->loadByProperties([
        'type' => $this->configuration['id'],
        'entity_id' => $data->currentNode->id(),
      ]);
    $current_gid = array_shift($current_gid);
    if (!empty($current_gid)) {
      $table = $data->ensureMyTable();

      $definition = array(
        'table' => 'group_content_field_data',
        'field' => 'entity_id',
        'left_table' => $table,
        'left_field' => 'nid',
      );

      $join = Views::pluginManager('join')
        ->createInstance('standard', $definition);
      $data->query->addRelationship('group_content_field_data', $join, 'group_content_field_data');

      foreach ($current_gid->get('gid')->getValue() as $value) {
        $data->query->addWhereExpression(0, "group_content_field_data.gid = :group_content_field_data_gid", array(':group_content_field_data_gid' => $value['target_id']));
      }
    }
  }

}
