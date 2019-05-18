<?php

namespace Drupal\qbank_dam\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\editor\Entity\Editor;
use Drupal\qbank_dam\QBankDAMService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the "qbankdam" plugin.
 *
 * NOTE: The plugin ID ('id' key) corresponds to the CKEditor plugin name.
 * It is the first argument of the CKEDITOR.plugins.add() function in the
 * plugin.js file.
 *
 * @CKEditorPlugin(
 *   id = "qbankdam",
 *   label = @Translation("Qbank damckeditor button")
 * )
 */
class QBankDAMCKEditorButton extends CKEditorPluginBase implements ContainerFactoryPluginInterface {

  protected $QAPI;

  /**
   * Constructs a Drupal\entity_embed\Plugin\CKEditorPlugin\DrupalEntity object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\Query\QueryInterface $embed_button_query
   *   The entity query object for embed button.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, QBankDAMService $qbank_api) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->QAPI = $qbank_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('qbank_dam.service')
    );
  }

  /**
   * {@inheritdoc}
   *
   * NOTE: The keys of the returned array corresponds to the CKEditor button
   * names. They are the first argument of the editor.ui.addButton() or
   * editor.ui.addRichCombo() functions in the plugin.js file.
   */
  public function getButtons() {
    // Make sure that the path to the image matches the file structure of
    // the CKEditor plugin you are implementing.
    return [
      'Qbankdam' => [
        'label' => t('Qbank damckeditor button'),
        'image' => drupal_get_path('module', 'qbank_dam') . '/js/plugins/qbankdam/images/icon.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    // Make sure that the path to the plugin.js matches the file structure of
    // the CKEditor plugin you are implementing.
    return drupal_get_path('module', 'qbank_dam') . '/js/plugins/qbankdam/plugin.js';
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
  public function getDependencies(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [
      'qbank_dam/connector',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [
      'qbank' => [
        'protocol' => $this->QAPI->getProtocol(),
        'deployment_site' => $this->QAPI->getDeploymentSite(),
        'url' => $this->QAPI->getApiUrl(),
        'token' => $this->QAPI->getToken(),
        'modulePath' => drupal_get_path('module', 'qbank_dam'),
        'html_id' => 'qbank-ckeditor-wrapper' . rand(),
        ]
    ];
  }

}
