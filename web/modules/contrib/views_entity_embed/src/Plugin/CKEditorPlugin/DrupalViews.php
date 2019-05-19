<?php

namespace Drupal\views_entity_embed\Plugin\CKEditorPlugin;

use Drupal\editor\Entity\Editor;
use Drupal\embed\EmbedCKEditorPluginBase;

/**
 * Defines the "drupalViews" plugin.
 *
 * @CKEditorPlugin(
 *   id = "drupalviews",
 *   label = @Translation("Views"),
 *   embed_type_id = "embed_views"
 * )
 */
class DrupalViews extends EmbedCKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'views_entity_embed') . '/js/plugins/drupalviews/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [
      'DrupalViews_dialogTitleAdd' => t('Insert views'),
      'DrupalViews_dialogTitleEdit' => t('Edit views'),
      'DrupalViews_buttons' => $this->getButtons(),
    ];
  }

}
