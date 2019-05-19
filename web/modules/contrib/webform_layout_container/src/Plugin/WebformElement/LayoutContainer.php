<?php

namespace Drupal\webform_layout_container\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\Container;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'layout container' type element to Webform.
 *
 * @WebformElement(
 *   id = "layout_container",
 *   label = @Translation("Layout container"),
 *   category = @Translation("Containers"),
 *   states_wrapper = TRUE
 * )
 */
class LayoutContainer extends Container {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      'align' => 'equal',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function build($format, array &$element, WebformSubmissionInterface $webform_submission, array $options = []) {
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['layout_container'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Alignment settings'),
      '#open' => TRUE,
    ];

    $form['layout_container']['align'] = [
      '#type' => 'select',
      '#title' => $this->t('Alignment'),
      '#description' => $this->t('Controls how elements are arranged in this container. Choose "horizontal" to create a row, or "equal width" to create a row with equal column widths. The "vertical" option can be used to make columns within a horizontal box.'),
      '#options' => [
        'horiz' => $this->t('Horizontal'),
        'equal' => $this->t('Equal Width'),
        'vert' => $this->t('Vertical'),
      ],
      '#required' => TRUE,
    ];

    return $form;
  }

}
