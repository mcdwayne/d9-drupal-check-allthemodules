<?php

namespace Drupal\link_header_pager\Plugin\views\pager;

use Drupal\views\Plugin\views\pager\SqlBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * The plugin to handle the link header pager.
 *
 * @ingroup views_pager_plugins
 *
 * @ViewsPager(
 *   id = "link_header",
 *   title = @Translation("Paged output, link header"),
 *   short_title = @Translation("Link Header"),
 *   help = @Translation("Paged output, links added to HTTP Link header")
 * )
 */
class LinkHeaderPager extends SqlBase implements LinkHeaderPagerInterface {

  /**
   * Pattern for a single link in the link header.
   */
  const HEADER_PATTERN = '<%s>; rel="%s"';

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    // remove unnecessary fields
    unset(
      $form['total_pages'],
      $form['tags']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    if (!empty($this->options['offset'])) {
      return $this->formatPlural($this->options['items_per_page'], '@count item, skip @skip', 'Link Header Paged, @count items, skip @skip', ['@count' => $this->options['items_per_page'], '@skip' => $this->options['offset']]);
    }
    return $this->formatPlural($this->options['items_per_page'], '@count item', 'Link Header Paged, @count items', ['@count' => $this->options['items_per_page']]);
  }

  /**
   * {@inheritdoc}
   */
  public function render($input) {
    // we don't render anything as our paging is in the header
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    // Calculate the maximum number of pages, starting counting with 0.
    $max = max(0, ceil(($this->getTotalItems()) / $this->getItemsPerPage() - 1));
    $currentPage = $this->getCurrentPage();
    $links = [];

    // are we not on the first page?
    if ($currentPage > 0) {
      $links[] = sprintf(static::HEADER_PATTERN, $this->getUrl($currentPage - 1), 'prev');
    }

    // are we not on the last page?
    if ($currentPage < $max) {
      $links[] = sprintf(static::HEADER_PATTERN, $this->getUrl($currentPage + 1), 'next');
    }

    $links[] = sprintf(static::HEADER_PATTERN, $this->getUrl(0), 'first');
    $links[] = sprintf(static::HEADER_PATTERN, $this->getUrl($max), 'last');

    return implode(', ', $links);
  }

  /**
   * Get the URL of the pager's view, at the specified page.
   *
   * @param int $page
   *   Page number (zero-indexed).
   */
  public function getUrl($page = NULL) {
    if (!isset($page)) {
      $page = $this->getCurrentPage();
    }

    return Url::fromRoute('<current>', [], [
      'absolute' => TRUE,
      'query' => pager_query_add_page($this->view->getExposedInput(), $this->getPagerId(), $page),
    ])
      ->toString();
  }

}
