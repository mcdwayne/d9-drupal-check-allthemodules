<?php

namespace Drupal\pagebreak\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "pagebreak" plugin.
 *
 * @CKEditorPlugin(
 *   id = "pagebreak",
 *   label = @Translation("PageBreak"),
 * )
 */
class PageBreak extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return 'libraries/pagebreak/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return ['fakeobjects'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'PageBreak' => [
        'label' => $this->t('PageBreak'),
        'image' => 'libraries/pagebreak/icons/pagebreak.png',
      ],
    ];
  }

}
