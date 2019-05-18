<?php

namespace Drupal\bueditor\Plugin\BUEditorPlugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\editor\Entity\Editor;
use Drupal\bueditor\BUEditorPluginBase;
use Drupal\bueditor\Entity\BUEditorEditor;
use Drupal\bueditor\BUEditorToolbarWrapper;

/**
 * Defines BUEditor Ajax Preview plugin.
 *
 * @BUEditorPlugin(
 *   id = "xpreview",
 *   label = "Ajax Preview"
 * )
 */
class XPreview extends BUEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'xpreview' => $this->t('Preview'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function alterEditorJS(array &$js, BUEditorEditor $bueditor_editor, Editor $editor = NULL) {
    $toolbar = BUEditorToolbarWrapper::set($js['settings']['toolbar']);
    // Check ajax preview button.
    if ($toolbar->has('xpreview')) {
      // Check access and add the library
      if (\Drupal::currentUser()->hasPermission('access ajax preview')) {
        $js['libraries'][] = 'bueditor/drupal.bueditor.xpreview';
      }
      else {
        $toolbar->remove('xpreview');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterToolbarWidget(array &$widget) {
    // Make xpreview definition available to toolbar widget
    $widget['libraries'][] = 'bueditor/drupal.bueditor.xpreview';
    // Add a tooltip
    $widget['items']['xpreview']['tooltip'] = $this->t('Requires ajax preview permission.');
  }

  /**
   * {@inheritdoc}
   */
  public function validateEditorForm(array &$form, FormStateInterface $form_state, BUEditorEditor $bueditor_editor) {
    // Warn about XPreview permission if it is newly activated.
    if (!$form_state->getErrors()) {
      if ($bueditor_editor->hasToolbarItem('xpreview')) {
        $ori = $bueditor_editor->isNew() ? NULL : $bueditor_editor->load($bueditor_editor->id());
        if (!$ori || !$ori->hasToolbarItem('xpreview')) {
          $msg = $this->t('Ajax preview button has been enabled. Please check <a href="@url">the required permissions</a>.', ['@url' => Url::fromRoute('user.admin_permissions')->toString()]);
          drupal_set_message($msg);
        }
      }
    }
  }

}
