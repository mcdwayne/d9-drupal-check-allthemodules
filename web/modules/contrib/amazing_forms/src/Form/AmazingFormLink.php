<?php

/**
 * @file
 * Contains Drupal\amazing_forms\AmazingFormLink
 */

namespace Drupal\amazing_forms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * AmazingFormLink class.
 */
class AmazingFormLink extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'amazing_forms_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $options = NULL) {
    $form['open_modal'] = [
        '#type' => 'link',
        '#title' => $this->t('Click here to open form'),
        '#url' => Url::fromRoute('amazing_forms.open_modal_form'),
        '#attributes' => [
            'class' => [
                'use-ajax',
                'button',
            ],
        ],
    ];

    // Attach the library for pop-up dialogs/modals.
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
  }

  protected function getEditableConfigNames() {
    return ['config.amazing_forms_form'];
  }

}
