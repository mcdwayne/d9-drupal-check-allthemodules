<?php
 
namespace Drupal\hubspot_embed\Plugin\CKEditorPlugin;
 
use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginButtonsInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;
 
/**
 * Defines the "HubspotEmbed" plugin.
 *
 * @CKEditorPlugin(
 *   id = "hubspot_embed",
 *   label = @Translation("Hubspot Embed")
 * )
 */
class HubspotEmbed extends PluginBase implements CKEditorPluginInterface, CKEditorPluginButtonsInterface {
 
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
    return drupal_get_path('module', 'hubspot_embed') . '/assets/hubspot_embed.js';
  }
 
  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'hubspot_embed' => [
        'label' => t('Hubspot Embed'),
        'image' => drupal_get_path('module', 'hubspot_embed') .  '/assets/icon.png',
      ]
    ];
  }
 
  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [
      'hubspot_embed_dialog_title' => t('Hubspot Embed'),
    ];
  }
}
