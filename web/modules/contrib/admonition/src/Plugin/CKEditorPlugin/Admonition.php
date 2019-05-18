<?php
/**
 * Created by PhpStorm.
 * User: kieran
 * Date: 8/6/16
 * Time: 10:22 AM
 */

namespace Drupal\admonition\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the plugin.
 *
 * @CKEditorPlugin(
 *   id = "admonition",
 *   label = @Translation("Admonition")
 * )
 */
class Admonition extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return array(
      'admonition' => array(
        'label' => t('Admonition'),
        'image' => drupal_get_path('module', 'admonition')
          . '/js/plugins/admonition/icons/admonition.png',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'admonition')
      . '/js/plugins/admonition/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $path = drupal_get_path('module', 'admonition') . '/templates/admonition.html';
    $template = file_get_contents($path);
    if ( $template === FALSE ) {
      throw new Exception('Admonition: Failed to read admonition template.');
    }
    return [ 'admonition_template' => $template ];
  }
}