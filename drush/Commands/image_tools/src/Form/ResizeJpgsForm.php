<?php

namespace Drupal\image_tools\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form Builder for resizing JPGs.
 */
class ResizeJpgsForm extends FormBase {

  /**
   * Get the Form Id.
   *
   * @return string
   */
  public function getFormId() {
    return 'image_tools_resize_jpgs_form';
  }

  /**
   * Build the Form.
   *
   * @param array $form
   *   Form Array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State.
   * @param int $max_width
   *   The Image max width.
   * @param bool $include_png
   *   Include Pngs.
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state, $max_width = 2048, $include_png = FALSE) {
    $form['include_png'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include PNGs'),
      '#default_value' => $include_png,
    ];

    $form['max_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Max Width'),
      '#default_value' => $max_width,
      '#min' => 1,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Form Submit Handler.
   *
   * @param array $form
   *   Form Array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Form Validation. Currently not called...
   *
   * @param array $form
   *   Form Array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $max_width = $form_state->getValue('max_width');

    if ($max_width <= 0) {
      $form_state->setErrorByName('max_width', $this->t('The max width must be at least 1 Pixel.'));
    }

  }

}
