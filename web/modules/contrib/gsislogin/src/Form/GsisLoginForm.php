<?php

namespace Drupal\gsislogin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements an social login form.
 */
class GsisLoginForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gsislogin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['gsis'] = [
      '#type' => 'inline_template',
      '#template' => '<a class="btn btn-block btn-lg btn-gsis" href="/gsis">
				    <i class="socicon-gsis"></i> ' . $this->t('Login via Taxisnet') . '
			      </a>',
      '#context' => [
        'name' => 'socicon',
      ],
    ];

    // Drupal magic.. loads library.
    $form['#attached']['library'][] = 'gsislogin/gsislogin';

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Nothing to validate here.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Never comes here.
    return TRUE;

  }

}
