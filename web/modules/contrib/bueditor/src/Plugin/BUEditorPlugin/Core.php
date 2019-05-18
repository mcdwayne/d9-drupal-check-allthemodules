<?php

namespace Drupal\bueditor\Plugin\BUEditorPlugin;

use Drupal\editor\Entity\Editor;
use Drupal\Component\Utility\Html;
use Drupal\bueditor\BUEditorPluginBase;
use Drupal\bueditor\Entity\BUEditorEditor;
use Drupal\bueditor\BUEditorToolbarWrapper;

/**
 * Defines BUEditor Core plugin.
 *
 * @BUEditorPlugin(
 *   id = "core",
 *   label = "Core",
 *   weight = -99
 * )
 */
class Core extends BUEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    // Buttons in core library
    $buttons = [
      '-' => $this->t('Separator'),
      '/' => $this->t('New line'),
      'bold' => $this->t('Bold'),
      'italic' => $this->t('Italic'),
      'underline' => $this->t('Underline'),
      'strike' => $this->t('Strikethrough'),
      'quote' => $this->t('Quote'),
      'code' => $this->t('Code'),
      'ul' => $this->t('Bulleted list'),
      'ol' => $this->t('Numbered list'),
      'link' => $this->t('Link'),
      'image' => $this->t('Image'),
      'undo' => $this->t('Undo'),
      'redo' => $this->t('Redo'),
    ];
    for ($i = 1; $i < 7; $i++) {
      $buttons['h' . $i] = $this->t('Heading @n', ['@n' => $i]);
    }
    return $buttons;
  }

  /**
   * {@inheritdoc}
   */
  public function alterEditorJS(array &$js, BUEditorEditor $bueditor_editor, Editor $editor = NULL) {
    // Add translation library for multilingual sites.
    $lang = \Drupal::service('language_manager')->getCurrentLanguage()->getId();
    if ($lang !== 'en' && \Drupal::service('module_handler')->moduleExists('locale')) {
      $js['libraries'][] = 'bueditor/drupal.bueditor.translation';
    }
    // Add custom button definitions and libraries.
    $toolbar = BUEditorToolbarWrapper::set($js['settings']['toolbar']);
    if ($custom_items = $toolbar->match('custom_')) {
      foreach (\Drupal::entityTypeManager()->getStorage('bueditor_button')->loadMultiple($custom_items) as $bid => $button) {
        $js['settings']['customButtons'][$bid] = $button->jsProperties();
        foreach ($button->get('libraries') as $library) {
          $js['libraries'][] = $library;
        }
      }
    }
    // Set editor id as the class name
    $cname = &$js['settings']['cname'];
    $cname = 'bue--' . $bueditor_editor->id() . (isset($cname) ? ' ' . $cname : '');
  }

  /**
   * {@inheritdoc}
   */
  public function alterToolbarWidget(array &$widget) {
    // Add custom button definitions.
    foreach (\Drupal::entityTypeManager()->getStorage('bueditor_button')->loadMultiple() as $bid => $button) {
      $item = $button->jsProperties();
      // Define template buttons as normal buttons with a special class name.
      if (!empty($item['template']) && empty($item['code'])) {
        $item['cname'] = 'template-button ficon-template' . (!empty($item['cname']) ? ' ' . $item['cname'] : '');
        $item['text'] = '<span class="template-button-text">' . (empty($item['text']) ? Html::escape($item['label']) : $item['text']) . '</span>';
        $item['label'] = '[' . $this->t('Template') . ']' . $item['label'];
        $item['multiple'] = TRUE;
      }
      // Remove unneeded properties.
      unset($item['template'], $item['code']);
      $widget['items'][$bid] = $item;
    }
  }

}
