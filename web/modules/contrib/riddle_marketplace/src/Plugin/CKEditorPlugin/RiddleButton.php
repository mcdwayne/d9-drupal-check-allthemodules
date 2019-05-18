<?php

namespace Drupal\riddle_marketplace\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginButtonsInterface;
use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginBase;

/**
 * Defines the "RiddleButton" plugin.
 *
 * @CKEditorPlugin(
 *   id = "RiddleButton",
 *   label = @Translation("RiddleButton")
 * )
 */
class RiddleButton extends CKEditorPluginBase implements CKEditorPluginInterface, CKEditorPluginButtonsInterface {

  /**
   * Riddle Feed Service.
   *
   * @var \Drupal\riddle_marketplace\RiddleFeedServiceInterface
   */
  private $riddleFeedService;

  /**
   * Riddle Marketplace Module configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $moduleSettings;

  /**
   * RiddleButton constructor.
   *
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->riddleFeedService = \Drupal::service('riddle_marketplace.feed');
    $this->moduleSettings = \Drupal::service('config.factory')
      ->get('riddle_marketplace.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public  function getLibraries(Editor $editor) {
    return [];

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
    return drupal_get_path('module', 'riddle_marketplace') . '/src/Plugin/CKEditorPlugin/editor_plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {

    return [
      'RiddleButton' => [
        'label' => 'Riddles',
        'image' => drupal_get_path('module', 'riddle_marketplace') . '/images/riddle.jpg',
        'image_alternative' => 'Riddles',
        'attributes' => [],
      ],
    ];
  }

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getConfig().
   *
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [
      'data' => json_encode($this->riddleFeedService->getFeed()),
      'riddle_url' => $this->moduleSettings->get('riddle_marketplace.url'),
    ];
  }

  /**
   * Gets the plugin_id of the plugin instance.
   *
   * @return string
   *   The plugin_id of the plugin instance.
   */
  public function getPluginId() {
    return 'RiddleButton';
  }

  /**
   * Gets the definition of the plugin implementation.
   *
   * @return array
   *   The plugin definition, as returned by the discovery object used by the
   *   plugin manager.
   */
  public function getPluginDefinition() {
    return [];
  }

}
