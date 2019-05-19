<?php

namespace Drupal\wysiwyg_linebreaks\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\Annotation\CKEditorPlugin;
use Drupal\Component\Plugin\PluginBase;
use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Annotation\Translation;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "linebreaks" plugin.
 *
 * @CKEditorPlugin(
 *   id = "linebreaks",
 *   label = @Translation("Linebreaks"),
 *   module = "wysiwyg_linebreaks"
 * )
 */
class Linebreaks extends PluginBase implements CKEditorPluginInterface, CKEditorPluginContextualInterface, CKEditorPluginConfigurableInterface {

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return array();
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
  public function getFile() {
    return drupal_get_path('module', 'wysiwyg_linebreaks') . '/js/plugins/linebreaks/linebreaks.js';
  }


  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $settings = $editor->getSettings();
    return array(
      'linebreaks_method' => isset($settings['plugins']['linebreaks']) ? $settings['plugins']['linebreaks']['method'] : 'force',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    // If the module is enabled, this plugin should be enabled.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $settings = $editor->getSettings();
    $form['method'] = array(
      '#type' => 'radios',
      '#title' => t('Conversion Method'),
      '#default_value' => isset($settings['plugins']['linebreaks']) ? $settings['plugins']['linebreaks']['method'] : 'force',
      '#options' => array(
        'force' => t('Force linebreaks'),
        'convert' => t('Convert linebreaks'),
      ),
      '#description' => t('Set to Force linebreaks if you never want to see <code>&lt;p&gt;</code> and <code>&lt;br /&gt;</code> tags in your content when editing without a Wysiwyg editor. Set to Convert linebreaks if you have content without <code>&lt;p&gt;</code> and <code>&lt;br /&gt;</code> tags that needs to be converted so it is still formatted correctly in the Wysiwyg editor.'),
    );

    return $form;
  }

}
