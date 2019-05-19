<?php

namespace Drupal\views_simple_math_field\Plugin\views\sort;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\sort\SortPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;

/**
 * Handler which sort by the similarity.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("views_simple_math_field_sort")
 */
class ViewsSimpleMathFieldSort extends SortPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['simple'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function postExecute(&$values) {
    $view = $this->view;
    $order = $this->options['order'];
    foreach ($this->view->result as $result) {
      $sm_field = $view->field[$this->options['simple']]->getValue($result);
      $this->view->result[$result->index]->{$this->options['simple']} = $sm_field;
    }
    if ($order === 'ASC') {
      usort($this->view->result, function ($item1, $item2) {
        return $item1->{$this->options['simple']} <=> $item2->{$this->options['simple']};
      });
    }
    else {
      usort($this->view->result, function ($item1, $item2) {
        return $item2->{$this->options['simple']} <=> $item1->{$this->options['simple']};
      });
    }

  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function showSortForm(&$form, FormStateInterface $form_state) {
    parent::showSortForm($form, $form_state);
    $simpleOptions = [];
    $fieldsOptions = $this->displayHandler->getFieldLabels();
    foreach ($fieldsOptions as $key => $value) {
      if (strpos($key, 'field_views_simple_math_field') !== FALSE) {
        $simpleOptions[$key] = $value;
      }
    }
    if (!empty($simpleOptions)) {
      $form['simple'] = [
        '#title' => $this->t('Simple math fields'),
        '#type' => 'radios',
        '#options' => $simpleOptions,
        '#default_value' => $this->options['simple'],
        '#required' => TRUE
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
  }

}
