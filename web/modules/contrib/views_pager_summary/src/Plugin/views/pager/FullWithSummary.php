<?php

namespace Drupal\views_pager_summary\Plugin\views\pager;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\pager\Full;

/**
 * The plugin to handle full pager with summary.
 *
 * @ingroup views_pager_plugins
 *
 * @ViewsPager(
 *   id = "full_with_summary",
 *   title = @Translation("Paged output, full pager with summary"),
 *   short_title = @Translation("Full with summary"),
 *   help = @Translation("Paged output, full Drupal style, with summary."),
 *   theme = "views_pager_with_summary",
 *   register_theme = FALSE
 * )
 */
class FullWithSummary extends Full {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['summary'] = ['default' => $this->t('@start-@end of @total')];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['summary'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Summary'),
      '#description' => $this->t('You may use HTML as well as @start, @end, and @total.'),
      '#default_value' => $this->options['summary'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render($input) {
    $result = parent::render($input);
    $result['#summary'] = $this->getSummary();
    return $result;
  }

  protected function getSummary() {
    return $this->replacePlaceholders($this->options['summary']);
  }

  protected function getStartNumber() {
    return 1 + ($this->getCurrentPage() * $this->getItemsPerPage());
  }

  protected function getEndNumber($start) {
    $total = $this->getTotalItems();
    $end = $start + $this->getItemsPerPage() - 1;

    if ($end < $start) {
      $end = $start;
    }

    if ($end > $total) {
      $end = $total;
    }

    return $end;
  }

  protected function replacePlaceholders($output) {
    $total = $this->getTotalItems();
    $start = $this->getStartNumber();
    $end = $this->getEndNumber($start);

    $output = str_replace('@total', $total, $output);
    $output = str_replace('@start', $start, $output);
    $output = str_replace('@end', $end, $output);

    return $output;
  }

}
