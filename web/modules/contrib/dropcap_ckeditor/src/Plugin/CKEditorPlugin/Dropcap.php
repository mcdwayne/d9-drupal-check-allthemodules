<?php

namespace Drupal\dropcap_ckeditor\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "dropcap_ckeditor" plugin.
 *
 * @CKEditorPlugin(
 *   id = "dropcap_ckeditor",
 *   label = @Translation("Dropcap Ckeditor"),
 *   module = "dropcap_ckeditor"
 * )
 */
class Dropcap extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface {
  
  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::isInternal().
   */
  public function isInternal( ) {
    return FALSE;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'dropcap_ckeditor') . '/js/plugins/dropcap/plugin.js';
  }
  
  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array(
      'dropcapCkeditor_dialogTitleAdd' => t('Add link'),
      'dropcapCkeditor_dialogTitleEdit' => t('Edit link'),
    );
  }
  
  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return array(
      'core/drupal.ajax',
    );
  }
  
  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return array(
      'Dropcap' => array(
        'label' => t('Dropcap'),
        'image' => drupal_get_path('module', 'dropcap_ckeditor') . '/js/plugins/dropcap/icons/dropcap.png',
      ),
    );
  }
  
  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $settings = $editor->getSettings();
    $form['dropcap_text'] = array(
      '#type' => 'checkbox',
      '#title' => t('Plugin to add dropcap'),
      '#attributes' => array('checked' => 'checked'),
      '#element_validate' => array(
        array($this, 'validateDropcapTextSelection'),
      ),
    );
    return $form;
  }
  
  /**
   * #element_validate handler for the "linkit_profile" element in settingsForm().
   */
  public function validateDropcapTextSelection(array $element, FormStateInterface $form_state) {
    $toolbar_buttons = $form_state->getValue(array('editor', 'settings', 'toolbar', 'button_groups'));
    if (strpos($toolbar_buttons, '"Dropcap"') !== FALSE && empty($element['#value'])) {
      $form_state->setError($element, t('Please select the dropcap text you wish to use.'));
    }
  }
}