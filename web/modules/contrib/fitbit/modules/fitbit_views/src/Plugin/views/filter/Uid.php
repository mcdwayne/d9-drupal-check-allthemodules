<?php

namespace Drupal\fitbit_views\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Simple filter to handle filtering Fitbit results by uid.
 *
 * @ViewsFilter("fitbit_uid")
 */
class Uid extends FilterPluginBase {

  public $no_operator = TRUE;

  protected $alwaysMultiple = TRUE;

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    $summary = parent::adminSummary();
    if (!empty($this->options['exposed'])) {
      $summary = $this->t('exposed');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#size' => 30,
      '#default_value' => $this->value,
    ];
  }
}
