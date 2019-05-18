<?php

namespace Drupal\ckeditor_shortcodes\Plugin\CKEditorPlugin;

use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginBase;

/**
 * Defines the "ckshortcodes" plugin.
 *
 * @CKEditorPlugin(
 *   id = "ckshortcodes",
 *   label = @Translation("CKEditor shortcodes button"),
 *   module = "ckshortcodes"
 * )
 */
class Btshortcodes extends CKEditorPluginBase {
	/**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getFile().
   */
  public function getFile() {
	return base_path() . 'libraries/ckshortcodes/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return array(
      'btsalerts' => array(
        'label' => t('Bootstrap Alerts'),
        'image' => base_path() . 'libraries/ckshortcodes/icons/btsalerts.png',
      ),
	  'btsaccordion' => array(
        'label' => t('Bootstrap Accordion'),
        'image' => base_path() . 'libraries/ckshortcodes/icons/btsaccordion.png',
      ),
	  'btsaccordions' => array(
        'label' => t('Bootstrap Accordions'),
        'image' => base_path() . 'libraries/ckshortcodes/icons/btsaccordions.png',
      ),
	  'btshr' => array(
        'label' => t('Bootstrap HR'),
        'image' => base_path() . 'libraries/ckshortcodes/icons/btshr.png',
      ),
	  'btsjumbotron' => array(
        'label' => t('Bootstrap Jumbotron'),
        'image' => base_path() . 'libraries/ckshortcodes/icons/btsjumbotron.png',
      ),
	  'btsprogress' => array(
        'label' => t('Bootstrap Progress'),
        'image' => base_path() . 'libraries/ckshortcodes/icons/btsprogress.png',
      ),
	  'btsrow' => array(
        'label' => t('Bootstrap Row'),
        'image' => base_path() . 'libraries/ckshortcodes/icons/btsrow.png',
      ),
	  'btscol' => array(
        'label' => t('Bootstrap Col'),
        'image' => base_path() . 'libraries/ckshortcodes/icons/btscol.png',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array();
  }

}