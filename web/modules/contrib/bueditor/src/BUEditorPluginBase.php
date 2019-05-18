<?php

namespace Drupal\bueditor;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;
use Drupal\bueditor\Entity\BUEditorEditor;

/**
 * Defines a base BUEditor plugin implementation.
 *
 * @see \Drupal\bueditor\BUEditorPluginInterface
 * @see \Drupal\bueditor\BUEditorPluginManager
 * @see plugin_api
 */
abstract class BUEditorPluginBase extends PluginBase implements BUEditorPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function alterEditorJS(array &$js, BUEditorEditor $bueditor_editor, Editor $editor = NULL) {
  }

  /**
   * {@inheritdoc}
   */
  public function alterToolbarWidget(array &$widget) {
  }

  /**
   * {@inheritdoc}
   */
  public function alterEditorForm(array &$form, FormStateInterface $form_state, BUEditorEditor $bueditor_editor) {
  }

  /**
   * {@inheritdoc}
   */
  public function validateEditorForm(array &$form, FormStateInterface $form_state, BUEditorEditor $bueditor_editor) {
  }
}
