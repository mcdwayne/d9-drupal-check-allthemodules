<?php

namespace Drupal\views_first_page\Plugin\views\pager;

use Drupal\Core\Form\FormStateInterface;

trait FirstPageTrait {

  /**
   * {@inheritdoc}
   */
  public function getItemsPerPage() {
    $key = $this->getCurrentPage() === 0
      ? 'items_first_page'
      : 'items_per_page';
    return isset($this->options[$key]) ? $this->options[$key] : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getPagerTotal() {
    $first_page_items = $this->options['items_first_page'];
    $items_per_page = $this->options['items_per_page'];
    return $this->total_items > $first_page_items
      ? ceil(1 + (($this->total_items - $first_page_items) / $items_per_page))
      : 1;
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    $options['items_first_page'] = ['default' => 10];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['items_per_page']['#weight'] = -50;
    $form['items_first_page'] = [
      '#title' => t('Items on first page'),
      '#type' => 'number',
      '#description' => t('The number of items to show on the first page'),
      '#default_value' => $this->options['items_first_page'],
      '#weight' => -49,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    parent::query();

    $items_per_page = $this->getItemsPerPage();
    $total_pages = $this->options['total_pages'];
    $unlimited_pages = empty($total_pages);
    $before_final_page = $this->current_page < $total_pages;

    $this->view->query->setLimit($items_per_page);

    if ($items_per_page > 0 && ($unlimited_pages || $before_final_page)) {
      $limit = $this->view->query->getLimit();
      $limit += 1;
      $this->view->query->setLimit($limit);
    }

    if ($this->current_page > 0) {
      $offset = $this->options['items_first_page'];
      $offset += ($this->current_page - 1) * $this->options['items_per_page'];
      $offset += $this->options['offset'];
      $this->view->query->setOffset($offset);
    }
  }

}
