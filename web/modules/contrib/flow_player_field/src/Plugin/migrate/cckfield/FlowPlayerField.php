<?php

namespace Drupal\flow_player_field\Plugin\migrate\cckfield;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\cckfield\CckFieldPluginBase;

/**
 * Plugin to migrate from the Drupal 7 flow_player_field module.
 *
 * @MigrateCckField(
 *   id = "flow_player_field",
 *   core = {7},
 *   source_module = "flow_player_field",
 *   destination_module = "flow_player_field",
 * )
 */
class FlowPlayerField extends CckFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldType(Row $row) {
    return 'flow_player_field';
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'default' => 'flow_player_field_video',
      'flow_player_field' => 'flow_player_field_video',
      'flow_player_field_thumbnail' => 'flow_player_field_thumbnail',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'flow_player_field_video' => 'flow_player_field_textfield',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function processCckFieldValues(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'iterator',
      'source' => $field_name,
      'process' => [
        'value' => 'video_url',
      ],
    ];
    $migration->mergeProcessOfProperty($field_name, $process);
  }

}
