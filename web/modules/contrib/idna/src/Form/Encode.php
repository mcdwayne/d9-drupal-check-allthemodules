<?php

namespace Drupal\idna\Form;

/**
 * @file
 * Contains Drupal\idna\Form\Encode.
 */

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Implements controller.
 */
class Encode extends FormBase {

  /**
   * Implements Ajax callback event.
   */
  public function ajaxSubmit($form, $form_state) {
    $response = new AjaxResponse();
    $otvet = "Idna Convert Encode:\n";
    $input = $form_state->getValue('input');
    foreach (explode("\n", $input) as $line) {
      $otvet .= \Drupal::service('idna')->encode(trim($line)) . "\n";
    }
    $response->addCommand(new HtmlCommand('#idna-encode-wrap', "<pre>{$otvet}</pre>"));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $extra = NULL) {

    $form_state->setCached(FALSE);
    $form['encode'] = [
      '#type' => 'details',
      '#title' => $this->t('Encode'),
      '#open' => TRUE,
    ];
    $form['encode']['input'] = [
      '#title' => $this->t('Domains'),
      '#type' => 'textarea',
    ];

    $form['encode']['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'submit',
      '#attributes' => ['class' => ['btn', 'btn-success']],
      '#value' => $this->t('Encode'),
      '#ajax'   => [
        'callback' => '::ajaxSubmit',
        'effect'   => 'fade',
        'progress' => ['type' => 'throbber', 'message' => NULL],
      ],
      '#suffix' => "<div id='idna-encode-wrap'></div>",
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'idna_encode';
  }

}
