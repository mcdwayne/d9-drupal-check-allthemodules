<?php

namespace Drupal\shorten\Form;

use Drupal\Core\Form\FormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a form which allows shortening of a URL via the UI.
 */
class ShortenFormCurrentPage extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shorten_current';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $form['#attached']['library'][] = 'shorten/shorten';

    $form['this_shortened'] = array(
      '#type' => 'textfield',
      '#size' => 25,
      '#default_value' => shorten_url(),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Nothing todo here.
  }
}
