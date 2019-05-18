<?php

namespace Drupal\contextual_cleaner\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\contextual_cleaner\Ajax\ContextualClearCommand;

/**
 * Class ContextualClearForm.
 */
class ContextualClearForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contextual_clear_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'contextual_cleaner/contextual_cleaner';
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear'),
      '#description' => $this->t('Clear contextual links.'),
      '#ajax' => [
        'callback' => '::clearContextualLinks',
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_flush_all_caches();
    drupal_set_message('Contextual links cleared.');
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return AjaxResponse
   */
  public function clearContextualLinks(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ContextualClearCommand());

    return $response;
  }

}
