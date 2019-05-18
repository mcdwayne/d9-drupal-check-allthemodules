<?php
 
/**
 * @file
 * Definition of \Drupal\hubspot_forms\Plugin\CKEditorPlugin\HubspotForms.
 */
 
namespace Drupal\hubspot_forms\Plugin\CKEditorPlugin;
 
use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginButtonsInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;
 
/**
 * Defines the "HubspotForms" plugin.
 *
 * @CKEditorPlugin(
 *   id = "hubspot_forms",
 *   label = @Translation("Hubspot Forms")
 * )
 */
class HubspotForms extends PluginBase implements CKEditorPluginInterface, CKEditorPluginButtonsInterface {
 
  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return [];
  }
 
  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [
      'core/drupal.ajax',
    ];
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
  public function getFile() {
    return drupal_get_path('module', 'hubspot_forms') . '/assets/hubspot_forms.js';
  }
 
  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'hubspot_forms' => [
        'label' => t('Hubspot Forms'),
        'image' => drupal_get_path('module', 'hubspot_forms') .  '/assets/icon.png',
      ]
    ];
  }
 
  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [
      'hubspot_forms_dialog_title' => t('Hubspot Forms'),
    ];
  }
}
