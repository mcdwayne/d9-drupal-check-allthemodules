<?php

namespace Drupal\views_sort_expression\Plugin\views\sort;


use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\sort\SortPluginBase;
use Drupal\views\Views;

/**
 * Allows to use any SQL expression
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("views_sort_expression")
 */
class ExpressionSort extends SortPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['expression'] = ['default' => NULL];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['expression'] = [
      '#type' => 'textarea',
      '#title' => t('Expression'),
      '#default_value' => $this->options['expression'],
      '#description' => t('<b>This is an advanced sort handler.</b> You can use whatever is available on the SQL. If what you need is not on the query, you could add other sort handlers in the end that will make some others to be available. You should probably want to enable "Show the SQL query" on the <a href=":url">settings</a> page.', [':url' => Url::fromRoute('views_ui.settings_basic')->toString()]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  public function query() {
    if (!empty($this->options['expression'])) {
      $alias = $this->realField . '_' . $this->position;
      $this->query->addOrderBy(NULL, $this->options['expression'], $this->options['order'], $alias);
    }
  }

}
