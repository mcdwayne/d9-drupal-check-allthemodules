<?php

namespace Drupal\simple_modal_entity_form\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;

/**
 * A base class for modal operations.
 */
abstract class ModalEntityOperationBase extends LinkBase {

  /**
   * {@inheritdoc}
   */
  public function renderText($alter) {
    if (isset($alter['url'])) {
      $options = $alter['url']->getOptions();
      $options['attributes']['class'][] = 'use-ajax';
      $options['attributes']['data-dialog-type'] = 'modal';
      $options['attributes']['data-dialog-options'] = json_encode([
        'width' => $this->options['width'] ? $this->options['width'] : '50%',
        'height' => $this->options['height'] ? $this->options['height'] : '500px'
      ]);

      $alter['url']->setOptions($options);
    }
    return parent::renderText($alter);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    $build = parent::render($row);
    $build['#attached']['library'][] = 'simple_modal_entity_form/simple_modal_entity_form.ajax';
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width'),
      '#default_value' => $this->options['width']
    ];
    $form['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Width'),
      '#default_value' => $this->options['height']
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['width'] = 800;
    $options['height'] = 500;
    return $options;
  }
}
