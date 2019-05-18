<?php

namespace Drupal\flow_player_wysiwyg\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\editor\Entity\Editor;

/**
 * The media_entity plugin for flow_player_field.
 *
 * @CKEditorPlugin(
 *   id = "flow_player",
 *   label = @Translation("Flowplayer WYSIWYG")
 * )
 */
class FlowPlayerWysiwyg extends CKEditorPluginBase implements CKEditorPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'flow_player_wysiwyg') . '/plugin/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'flow_player' => [
        'label' => $this->t('Flow Player'),
        'image' => drupal_get_path('module', 'flow_player_wysiwyg') . '/plugin/icon.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

}
