<?php

namespace Drupal\cmlexchange\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Implements the form controller.
 */
class Import extends FormBase {
  private $wrapper = 'cml-import-wrapper';

  /**
   * AJAX Import.
   */
  public function ajaxSubmit(array &$form, $form_state) {
    $otvet = "import: ";
    $import = \Drupal::service('cmlexchange.import_pipeline');
    $otvet .= $import->process($form_state->extra, TRUE);
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand("#" . $this->wrapper, "<pre>{$otvet}</pre>"));
    return $response;
  }

  /**
   * AJAX Continue.
   */
  public function ajaxSubmitContinue(array &$form, $form_state) {
    $otvet = "import: ";
    $import = \Drupal::service('cmlexchange.import_pipeline');
    $otvet .= $import->process($form_state->extra);
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand("#" . $this->wrapper, "<pre>{$otvet}</pre>"));
    return $response;
  }

  /**
   * Build the simple form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $extra = NULL) {
    $form_state->extra = $extra;
    $form_state->setCached(FALSE);
    $form["import"] = [
      '#value' => $this->t('Import'),
      '#type' => 'submit',
      '#ajax'   => [
        'callback' => '::ajaxSubmit',
        'effect'   => 'fade',
        'progress' => ['type' => 'throbber', 'message' => NULL],
      ],
    ];
    $form["continue"] = [
      '#value' => $this->t('Continue'),
      '#type' => 'submit',
      '#ajax'   => [
        'callback' => '::ajaxSubmitContinue',
        'effect'   => 'fade',
        'progress' => ['type' => 'throbber', 'message' => NULL],
      ],
    ];
    $form['#suffix'] = '<div id="' . $this->wrapper . '"></div>';
    return $form;
  }

  /**
   * Implements a form submit handler.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cml-import';
  }

}
