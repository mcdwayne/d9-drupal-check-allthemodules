<?php

namespace Drupal\similarterms\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Shows the similarity of the node.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("similar_terms_field")
 */
class SimilarTermsField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $params = array(
      'function' => 'count',
    );
    $this->field_alias = $this->query->addField('node', 'nid', NULL, $params);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['count_type'] = array('default' => 1);
    $options['percent_suffix'] = array('default' => 1);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['count_type'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Display type'),
      '#default_value' => $this->options['count_type'],
      '#options' => array(
        0 => $this->t('Show count of common terms'),
        1 => $this->t('Show as percentage'),
      ),
    );

    $form['percent_suffix'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Append % when showing percentage'),
      '#default_value' => !empty($this->options['percent_suffix']),
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {

    if ($this->options['count_type'] == 0) {
      return $values->{$this->field_alias};
    }
    elseif ($this->view->tids) {
      $output = round($values->{$this->field_alias} / count($this->view->tids) * 100);
      if (!empty($this->options['percent_suffix'])) {
        $output .= '%';
      }
      return $output;
    }
  }

}
