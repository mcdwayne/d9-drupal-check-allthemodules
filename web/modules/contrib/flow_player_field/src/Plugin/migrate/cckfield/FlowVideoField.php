<?php

namespace Drupal\flow_player_field\Plugin\migrate\cckfield;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\cckfield\CckFieldPluginBase;

/**
 * Plugin to migrate from the Drupal 6 emfield module.
 *
 * @MigrateCckField(
 *   id = "flowvideo",
 *   core = {6},
 *   source_module = "emfield",
 *   destination_module = "flow_player_field",
 * )
 */
class FlowVideoField extends CckFieldPluginBase {

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
      'video' => 'flow_player_field_video',
      'thumbnail' => 'flow_player_field_thumbnail',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'emvideo_textfields' => 'flow_player_field_textfield',
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
        'value' => 'embed',
      ],
    ];
    $migration->mergeProcessOfProperty($field_name, $process);
  }

}
