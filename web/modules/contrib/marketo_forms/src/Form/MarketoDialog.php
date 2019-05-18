<?php

/**
 * @file
 * Contains \Drupal\marketo_forms\Form\MarketoDialog.
 */

namespace Drupal\marketo_forms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\marketo_forms\MarketoFormsCore;

/**
 * Provides Marketo Forms dialog for text editors.
 */
class MarketoDialog extends FormBase implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'marketo_forms_dialog';
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\filter\Entity\FilterFormat $filter_format
   *   The filter format for which this dialog corresponds.
   */
  public function buildForm(array $form, FormStateInterface $form_state, FilterFormat $filter_format = NULL) {
    // The default values are set directly from \Drupal::request()->request,
    // provided by the editor plugin opening the dialog.
    $user_input = $form_state->getUserInput();
    $input = isset($user_input['editor_object']) ? $user_input['editor_object'] : [];

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#prefix'] = '<div id="marketo-forms-dialog-form">';
    $form['#suffix'] = '</div>';

    $form['formid'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Marketo Form'),
      '#description'   => $this->t('Please choose a form you would like to display.'),
      '#default_value' => '',
      '#required'      => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['save_modal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Insert'),
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => [],
      '#ajax' => [
        'callback' => '::submitForm',
        'event' => 'click',
      ],
    ];

    // This is the element where we put generated code
    // By doing this we can generate [video:url]
    // in PHP instead of generating it in CKEditor JS plugin.
    $form['attributes']['code'] = [
      '#title' => $this->t('Marketo Forms'),
      '#type' => 'textfield',
      '#prefix' => '<div class="visually-hidden">',
      '#suffix' => '</div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Generate shortcut/token code.
    $form_empty = TRUE;
    $shortcode = '[marketo-form:';
    if ($form_state->getValue('formid')) {
      $form_id = $form_state->getValue('formid');
      $shortcode .= $form_id;
      $form_empty = FALSE;
    }
    $shortcode .= ']';

    if ( !empty($shortcode) && !$form_empty ) {
      $form_state->setValue(['attributes', 'code'], $shortcode);
    }

    if ($form_state->getErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#marketo-forms-dialog-form', $form));
    }
    else {
      $response->addCommand(new EditorDialogSave($form_state->getValues()));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

}
