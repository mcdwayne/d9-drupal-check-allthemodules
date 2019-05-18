<?php

/**
 * @file
 * Contains \Drupal\elfinder\Plugin\BUEditorPlugin\elFinder.
 */

namespace Drupal\elfinder\Plugin\BUEditorPlugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;
use Drupal\bueditor\BUEditorPluginBase;
use Drupal\bueditor\Entity\BUEditorEditor;
use Drupal\elfinder\Controller\elFinderPageController as elFinderPageController;

/**
 * Defines elFinder as a BUEditor plugin.
 *
 * @BUEditorPlugin(
 *   id = "elfinder",
 *   label = "elFinder File Manager"
 * )
 */
class elFinder extends BUEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function alterEditorJS(array &$js, BUEditorEditor $bueditor_editor, Editor $editor = NULL) {
    if (isset($js['settings']['fileBrowser']) && $js['settings']['fileBrowser'] === 'elfinder') {
      $js['libraries'][] = 'elfinder/drupal.elfinder';
      $js['libraries'][] = 'elfinder/drupal.elfinder.bueditor';
      $browserpage = elFinderPageController::buildBrowserPage(TRUE);
      $js['settings']['elfinder'] = $browserpage['#attached']['drupalSettings']['elfinder'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterEditorForm(array &$form, FormStateInterface $form_state, BUEditorEditor $bueditor_editor) {
    // Add elFinder option to file browser field.
    $fb = &$form['settings']['fileBrowser'];
    $fb['#options']['elfinder'] = $this->t('elFinder');
    // Add configuration link
    $form['settings']['elfinder'] = array(
      '#type' => 'container',
      '#states' => array(
        'visible' => array(':input[name="settings[fileBrowser]"]' => array('value' => 'elfinder')),
      ),
      '#attributes' => array(
        'class' => array('description'),
      ),
      'content' => array(
        '#markup' => $this->t('Configure <a href="!url">elFinder File Manager</a>.', array('!url' => \Drupal::url('elfinder.admin')))
      ),
    );
    // Set weight
    if (isset($fb['#weight'])) {
      $form['settings']['elfinder']['#weight'] = $fb['#weight'] + 0.1;
    }
    
    //$browserpage = elFinderPageController::buildBrowserPage(FALSE);
    drupal_set_message('99');
  //  $form['#attached']['drupalSettings']['elfinder'] = $browserpage['#attached']['drupalSettings']['elfinder'];
  }

}
