<?php

namespace Drupal\cms_content_sync_migrate_acquia_content_hub;

use Drupal\cms_content_sync\Entity\EntityStatus;
use Drupal\cms_content_sync\Entity\Flow;
use Drupal\node\Entity\Node;
use Drupal\Core\Controller\ControllerBase;

/**
 *
 */
class CreateStatusEntities extends ControllerBase {

  /**
   * Collect relevant nodes.
   *
   * @param $flow_id
   * @param $flow_configurations
   * @param $pools
   * @param $type
   * @param bool $execute
   */
  public function prepare($flow_id, $flow_configurations, $pools, $type) {

    // Check in which node bundle, that are configured within the flow, a reference field does exist.
    $reference_fields = \Drupal::service('entity_field.manager')->getFieldMapByFieldType('entity_reference');

    $operations = [];

    if (isset($reference_fields['node'])) {
      foreach ($reference_fields['node'] as $key => $node_reference_field) {
        foreach ($node_reference_field['bundles'] as $bundle_id => $bundle) {

          if (array_key_exists($bundle_id, $flow_configurations['node'])) {

            // Check if the field is referencing taxonomy terms.
            $field = \Drupal::entityTypeManager()->getStorage('field_storage_config')->load('node.' . $key);

            if ($field) {
              $target_type = $field->getSetting('target_type');

              if (isset($target_type) && $target_type == 'taxonomy_term') {

                // Load all matching nodes and check if they have a reference to a term a pool has been created for.
                $nids = \Drupal::entityQuery('node')->condition('type', $bundle_id)->execute();

                foreach ($nids as $nid) {
                  $operations[] = [
                    __NAMESPACE__ . '\CreateStatusEntities::execute',
                    [$nid, $flow_id, $bundle_id, $pools, $key, $type],
                  ];
                }
              }
            }
          }
        }
      }
    }

    return $operations;
  }

  /**
   * Batch create Status Entities for collected nodes.
   *
   * @param $nid
   * @param $flow_id
   * @param $bundle_id
   * @param $pools
   * @param $field_name
   * @param $type
   */
  public static function execute($nid, $flow_id, $bundle_id, $pools, $field_name, $type) {

    /**
     * @var \Drupal\node\Entity\NodeInterface $node
     */
    $node = Node::load($nid);
    $reference_values = $node->get($field_name)->getValue();
    if (!empty($reference_values)) {

      foreach ($reference_values as $reference_value) {

        foreach ($pools as $pool_id => $pool) {

          if ($reference_value['target_id'] == $pool['term_id']) {

            // If a node has a match, create a status entity.
            // Ensure that a status entity does not already exist.
            $entity_status = EntityStatus::getInfoForEntity('node', $node->uuid(), $flow_id, $pool_id);
            if (!$entity_status) {
              $data = [
                'flow' => $flow_id,
                'pool' => $pool_id,
                'entity_type' => 'node',
                'entity_uuid' => $node->uuid(),
                'entity_type_version' => Flow::getEntityTypeVersion('node', $bundle_id),
                'flags' => 0,
                'source_url' => NULL,
              ];

              $data['last_' . $type] = $node->getChangedTime();

              $entity_status = EntityStatus::create($data);

              if ($type == 'export') {
                $entity_status->isExportEnabled(TRUE);
                $entity_status->isSourceEntity(TRUE);
              }
              $entity_status->save();
            }
          }
        }
      }
    }
  }

}
