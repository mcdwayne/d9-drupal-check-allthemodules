<?php

namespace Drupal\block_ajax;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Block\BlockBase;

/**
 * Defines an ajax block abstract implementation.
 *
 * This abstract implementation enables any block to be updated utilising
 * the RefreshBlockAjaxCommand.
 *
 * @see \Drupal\block_ajax\Ajax\RefreshBlockAjaxCommand
 * @ingroup block_api
 */
abstract class AjaxBlockBase extends BlockBase {

  /**
   * @var string
   */
  protected $ajax_id;

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $temp_storage;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $this->temp_storage = \Drupal::service('user.private_tempstore')->get('block_ajax');
    $this->uuid = \Drupal::service('uuid');

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    if (!empty($configuration['ajaxId'])) {
      if (count($configuration) > 1) {
        throw new \Exception('No other than ajaxId configuration value is allowed when it\'s present.');
      }

      // Apply stored configuration, if such exists.
      $configuration = $this->temp_storage->get($configuration['ajaxId']);
    }
    // In case an ajax id has not been assigned, create one.
    else {
      $ajax_id = $this->generateAjaxId($configuration);

      $this->temp_storage->set(
        $ajax_id,
        $configuration
      );
    }

    // Make sure the configuration value is not null.
    if (!$configuration) {
      $configuration = [];
    }

    parent::setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'ajax_id' => $this->getAjaxId(),
    ];
  }

  /**
   * Returns a unique ajax id for the block.
   *
   * @return string
   */
  public function getAjaxId() {
    return $this->configuration['ajaxId'];
  }

  /**
   * Generates and sets ajax id configuration value.
   *
   * @param array $configuration
   *
   * @return string
   */
  public function generateAjaxId(array &$configuration) {
    return $configuration['ajaxId'] = $this->uuid->generate();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $ajax_id = $this->configuration['ajaxId'];

    return [
      '#attributes' => [
        'class' => [$this->pluginId],
        'data-block-id' => $ajax_id,
      ],
      '#attached' => [
        'library' => ['block_ajax/refresh_ajax_block_library'],
        'drupalSettings' => [
          'ajaxBlocks' => [
            $ajax_id => [
              'plugin_id' => $this->pluginId,
              'config' => [
                'ajaxId' => $ajax_id,
              ],
            ],
          ],
        ],
      ],
    ];
  }

}
