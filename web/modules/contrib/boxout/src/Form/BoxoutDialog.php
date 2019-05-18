<?php

namespace Drupal\boxout\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\Core\Ajax\CloseModalDialogCommand;

/**
 * Provides a boxout dialog for text editors.
 */
class BoxoutDialog extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'editor_boxout_dialog';
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\filter\Entity\FilterFormat $filter_format
   *   The filter format for which this dialog corresponds.
   */
  public function buildForm(array $form, FormStateInterface $form_state, FilterFormat $filter_format = NULL) {
    // The default values are set directly from \Drupal::request()->request,
    // provided by the boxout plugin opening the dialog.
    $user_input = $form_state->getUserInput();
    $input = isset($user_input['editor_object']) ? $user_input['editor_object'] : array();

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#attached']['library'][] = 'boxout/boxout.dialog';
    $form['#prefix'] = '<div id="editor-boxout-dialog-form">';
    $form['#suffix'] = '</div>';

    $form['attributes']['style'] = array(
      '#id' => 'boxout-style',
      '#title' => $this->t('Style'),
      '#type' => 'select',
      '#options' => array(
        'default' => $this->t('Default'),
        'plain' => $this->t('Plain'),
      ),
      '#default_value' => isset($input['style']) ? $input['style'] : 'default',
    );
    $form['attributes']['header'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Header'),
      '#default_value' => isset($input['header']) ? $input['header'] : '',
      '#attributes' => ['class' => ['dialog-header']],
      '#size' => 40,
      '#maxlength' => 256,
    );
    $options = [
      'p' => '<p>',
      'h2' => '<h2>',
      'h3' => '<h3>',
      'h4' => '<h4>',
      'h5' => '<h5>',
    ];
    $form['attributes']['header_element_type'] = array(
      '#id' => 'boxout-element-type',
      '#type' => 'select',
      '#title' => $this->t('Element type'),
      '#options' => $options,
      '#default_value' => isset($input['header_element_type']) ? $input['header_element_type'] : 'h2',
      '#attributes' => ['class' => ['dialog-header-type']],
    );
    $form['attributes']['body'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => isset($input['body']) ? $input['body'] : '',
      '#size' => 50,
    );

    $form['actions'] = array(
      '#type' => 'actions',
    );
    $form['actions']['save_modal'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Insert'),
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => array(),
      '#ajax' => array(
        'callback' => '::submitForm',
        'event' => 'click',
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    if ($form_state->getErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#editor-boxout-dialog-form', $form));
    }
    else {
      $response->addCommand(new EditorDialogSave($form_state->getValues()));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

}
