<?php

namespace Drupal\mynotes\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a 'Quick Note' block.
 *
 * @Block(
 *   id = "quick_note_block",
 *   admin_label = @Translation("Quick Note block"),
 * )
 */
class QuickNoteBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = [];
    $form['open_modal'] = [
      '#type' => 'link',
      '#title' => $this->t('Quick note'),
      '#url' => Url::fromRoute('mynotes.quick_note'),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'button',
        ],
      ],
    ];
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    return $form;
  }

}
