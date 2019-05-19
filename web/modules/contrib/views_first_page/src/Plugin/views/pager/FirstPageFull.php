<?php

namespace Drupal\views_first_page\Plugin\views\pager;

use Drupal\views\Plugin\views\pager\Full;

/**
 * @ViewsPager(
 *  id = "first_page_full_pager",
 *  title = @Translation("Paged output, separate count on first page"),
 *  short_title = @Translation("Full pager with separate first page"),
 *  help = @Translation("Paged output with separate count on the first page"),
 *  theme = "pager",
 *  register_theme = FALSE
 * )
 */
class FirstPageFull extends Full {

  use FirstPageTrait;

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    if (empty($this->options['items_first_page'])) {
      return parent::summaryTitle();
    }
    if (!empty($this->options['offset'])) {
      return $this->formatPlural($this->options['items_per_page'],
        '@count item, skip @skip, @first on first page',
        'Paged, @count items, skip @skip, @first on first page',
        [
          '@count' => $this->options['items_per_page'],
          '@skip' => $this->options['offset'],
          '@first' => $this->options['items_first_page'],
        ]
      );
    }
    return $this->formatPlural($this->options['items_per_page'],
      '@count item, @first on first page',
      'Paged, @count items, @first on first page',
      [
        '@count' => $this->options['items_per_page'],
        '@first' => $this->options['items_first_page'],
      ]
    );
  }

}
