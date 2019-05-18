<?php

namespace Drupal\ckeditor_giella\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "Giella" plugin.
 *
 * @CKEditorPlugin(
 *   id = "giella",
 *   label = @Translation("CKEditor Giella Plugin"),
 *   module = "ckeditor_giella"
 * )
 */
class Giella extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface {

  /**
   * Get path to library folder.
   */
  public static function getLibraryPath() {
    $path = 'libraries/ckeditor.giella';
    if (\Drupal::moduleHandler()->moduleExists('libraries')) {
      $path = libraries_get_path('ckeditor.giella', TRUE);
    }

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return $this->getLibraryPath() . '/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'Giella' => [
        'label' => $this->t('Giella'),
        'image' => $this->getLibraryPath() . '/icons/hidpi/giella.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $settings = $editor->getSettings();
    if (isset($settings['plugins']['giella'])) {
      $config = $settings['plugins']['giella'];
    }

    return [
      'giella_multiLanguageMode' => TRUE,
      'giella_autoStartup' => isset($config['auto_startup']) ? (bool) $config['auto_startup'] : FALSE,
      'giella_sLang' => 'se',
      'giella_servicePath' => 'http://divvun.no:3000/spellcheck31/script/ssrv.cgi',
      'giella_srcUrl' => 'http://divvun.no:3000/spellcheck/lf/giella3/ckgiella/ckgiella.js',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $config = ['auto_startup' => FALSE];
    $settings = $editor->getSettings();
    if (isset($settings['plugins']['giella'])) {
      $config = $settings['plugins']['giella'];
    }

    $form['auto_startup'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Startup automatically?'),
      '#description' => $this->t('Check, if you like to enable the Giella spellcheck automatically.'),
      '#default_value' => $config['auto_startup'],
    ];

    return $form;
  }

}
