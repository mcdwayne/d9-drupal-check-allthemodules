<?php

namespace Drupal\paragraphs_entity_embed\Plugin\CKEditorPlugin;

use Drupal\editor\Entity\Editor;
use Drupal\embed\EmbedCKEditorPluginBase;

/**
 * Defines the "drupalparagraph" plugin.
 *
 * @CKEditorPlugin(
 *   id = "drupalparagraph",
 *   label = @Translation("Paragraph"),
 *   embed_type_id = "paragraphs_entity_embed"
 * )
 */
class DrupalParagraph extends EmbedCKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'paragraphs_entity_embed') . '/js/plugins/drupalparagraph/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [
      'DrupalParagraph_dialogTitleAdd' => t('Insert Paragraph'),
      'DrupalParagraph_dialogTitleEdit' => t('Edit Paragraph'),
      'DrupalParagraph_buttons' => $this->getButtons(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    $libraries = parent::getLibraries($editor);
    $libraries[] = 'paragraphs_entity_embed/dialog';
    return $libraries;
  }

}
