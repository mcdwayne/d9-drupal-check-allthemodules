<?php

namespace Drupal\bueditor;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;
use Drupal\bueditor\Entity\BUEditorEditor;

/**
 * Defines an interface for BUEditor plugins.
 *
 * @see \Drupal\bueditor\BUEditorPluginBase
 * @see \Drupal\bueditor\BUEditorPluginManager
 * @see plugin_api
 */
interface BUEditorPluginInterface extends PluginInspectionInterface {

  /**
   * Returns plugin buttons.
   *
   * @return array
   *   An array of id:label pairs.
   */
  public function getButtons();

  /**
   * Alters JS data of a BUEditor Editor.
   *
   * @param array $js
   *   An associative array that holds 'libraries' and 'settings' of the editor.
   * @param \Drupal\bueditor\Entity\BUEditorEditor $bueditor_editor
   *   BUEditor Editor entity that owns the data.
   * @param \Drupal\editor\Entity\Editor $editor
   *   An optional Editor entity which the BUEditor Editor is attached to.
   */
  public function alterEditorJS(array &$js, BUEditorEditor $bueditor_editor, Editor $editor = NULL);

  /**
   * Alters the toolbar widget used in BUEditor Editor form.
   *
   * @param array $widget
   *   An associative array that holds 'libraries' and 'items' in it.
   */
  public function alterToolbarWidget(array &$widget);

  /**
   * Alters entity form of a BUEditor Editor.
   */
  public function alterEditorForm(array &$form, FormStateInterface $form_state, BUEditorEditor $bueditor_editor);

  /**
   * Validates entity form of a BUEditor Editor.
   */
  public function validateEditorForm(array &$form, FormStateInterface $form_state, BUEditorEditor $bueditor_editor);
}
