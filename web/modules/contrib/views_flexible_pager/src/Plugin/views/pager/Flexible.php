<?php

namespace Drupal\views_flexible_pager\Plugin\views\pager;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\pager\Full;

/**
 * Pager plugin allowing a different number of items on the first page.
 *
 * @ingroup views_pager_plugins
 *
 * @ViewsPager(
 *   id = "views_flexible_pager",
 *   title = @Translation("Paged output, full pager, optional different number of items on first page"),
 *   short_title = @Translation("Flexible"),
 *   help = @Translation("Paged output, full pager, optional different number of items on first page"),
 *   theme = "pager",
 *   register_theme = FALSE
 * )
 */
class Flexible extends Full
{

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state)
  {
    parent::buildOptionsForm($form, $form_state);

    $form['initial'] = [
      '#type' => 'number',
      '#title' => $this->t('Initial items'),
      '#description' => $this->t('The number of items to display on the first page. Enter 0 to use the same as items per page.'),
      '#default_value' => $this->options['initial'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query()
  {
    parent::query();

    // Set first page items limit.
    $other_pages = $this->options['items_per_page'];
    $limit = !empty($this->options['initial']) ? $this->options['initial'] : $other_pages;
    $offset = !empty($this->options['offset']) ? $this->options['offset'] : 0;

    if ($this->current_page != 0) {
      $offset = $limit + (($this->current_page - 1) * $other_pages) + $offset;
      $limit = $other_pages;
    }

    $this->view->query->setLimit($limit);
    $this->view->query->setOffset($offset);
  }

  /**
   * {@inheritdoc}
   */
  public function getPagerTotal() {
    if ($items_per_page = intval($this->getItemsPerPage())) {
      $initial_items = $this->options['initial'];
      return ceil(($this->total_items - $initial_items) / $items_per_page) + 1;
    }
    else {
      return 1;
    }
  }

}