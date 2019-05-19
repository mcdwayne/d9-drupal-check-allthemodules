<?php

namespace Drupal\views_first_page\Plugin\views\pager;


use Drupal\views\Plugin\views\pager\Mini;

/**
 * @ViewsPager(
 *  id = "first_page_mini_pager",
 *  title = @Translation("Paged output, mini pager, separate count on first page"),
 *  short_title = @Translation("Mini pager with separate first page"),
 *  help = @Translation("A simple pager with separate count on the first page, previous and next links"),
 *  theme = "pager",
 *  register_theme = FALSE
 * )
 */
class FirstPageMini extends Mini {

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
        'Mini pager, @count item, skip @skip, @first on first page',
        'Mini pager, @count items, skip @skip, @first on first page',
        [
          '@count' => $this->options['items_per_page'],
          '@skip' => $this->options['offset'],
          '@first' => $this->options['items_first_page'],
        ]
      );
    }
    return $this->formatPlural($this->options['items_per_page'],
      'Mini pager, @count item, @first on first page',
      'Mini pager, @count items, @first on first page',
      [
        '@count' => $this->options['items_per_page'],
        '@first' => $this->options['items_first_page'],
      ]
    );
  }

}
