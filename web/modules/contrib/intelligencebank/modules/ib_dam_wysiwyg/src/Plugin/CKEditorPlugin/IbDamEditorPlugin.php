<?php

namespace Drupal\ib_dam_wysiwyg\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;
use Drupal\file\Plugin\Field\FieldType\FileItem;

/**
 * IbDamEditorPlugin class.
 *
 * @CKEditorPlugin(
 *   id = "ib_dam_browser",
 *   label = @Translation("IntelligenceBank Asset Browser")
 * )
 */
class IbDamEditorPlugin extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface {

  const DEFAULT_FILE_EXTENSIONS = 'jpg jpeg gif png txt doc docx xls pdf ppt pps odt ods odp mp3 mp4';

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [
      'ib_dam/common',
      'core/underscore',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'ib_dam_wysiwyg') . '/js/plugins/ib_dam_browser/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'ib_dam_browser' => [
        'name' => 'IB',
        'label' => $this->t('IntelligenceBank Asset Browser'),
        'image' => drupal_get_path('module', 'ib_dam_wysiwyg') . '/js/plugins/ib_dam_browser/icon.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $settings = [];
    if (!empty($editor->getSettings()['plugins']['ib_dam_browser'])) {
      $settings = $editor->getSettings()['plugins']['ib_dam_browser'];
    }

    $form['upload_location'] = [
      '#title' => $this->t('Upload location'),
      '#type' => 'textfield',
      '#default_value' => isset($settings['upload_location'])
        ? $settings['upload_location']
        : 'public://intelligencebank',
    ];

    $form['file_extensions'] = [
      '#title' => $this->t('Allowed file extensions list'),
      '#type' => 'textfield',
      '#default_value' => isset($settings['file_extensions'])
        ? $settings['file_extensions']
        : static::DEFAULT_FILE_EXTENSIONS,
    ];

    $form['allow_embed'] = [
      '#title' => $this->t('Allow public assets'),
      '#type' => 'checkbox',
      '#default_value' => isset($settings['allow_embed'])
        ? (bool) $settings['allow_embed']
        : TRUE,
    ];

    $form['upload_location']['#element_validate'][] = [FileItem::class, 'validateDirectory'];
    $form['file_extensions']['#element_validate'][] = [FileItem::class, 'validateExtensions'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [
      'DrupalIbDamBrowser_dialogTitleAdd' => $this->t('IntelligenceBank Asset Browser'),
      'DrupalIbDamBrowser_editorButtonLabel' => $this->t('IntelligenceBank Asset Browser'),
      'DrupalIbDamBrowser_messageUseEmptyOrIbElement' => $this->t('Asset Browser can be run on existing element or same type element.<br>Create an empty &lt;p&gt; or select existing IntelligenceBank asset element.'),
    ];
  }

}
