<?php

namespace Drupal\docbinder\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a DocBinder Block.
 *
 * @Block(
 *   id = "docbinder_block",
 *   admin_label = @Translation("DocBinder"),
 * )
 */
class DocBinderBlock extends BlockBase {

  /**
   * The tempstore.
   *
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $tempStore;

  /**
   * {@inheritdoc}
   */
  public function build() {

    $this->tempStore = \Drupal::service('tempstore.private')->get('docbinder');
    $files_count = count($this->tempStore->get('files'));
    $html = '<p>' . \Drupal::translation()->formatPlural($files_count, 'There <span id="docbinder-file-count">is 1 file</span> in your collection.', 'There <span id="docbinder-file-count">are @count files</span> in your collection.') . '</p>';
    $html .= '<p><a class="docbinder-docbinder" href="/docbinder">' . $this->t('Review collection') . '</a></p>';

    return array(
      '#markup' => $html,
      '#cache' => array(
        'max-age' => 0,
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_config = \Drupal::config('docbinder.settings');
    return [
      'label' => $default_config->get('collection.name'),
    ];
  }
}
