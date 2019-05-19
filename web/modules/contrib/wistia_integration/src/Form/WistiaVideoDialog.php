<?php

namespace Drupal\wistia_integration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\Core\Ajax\CloseModalDialogCommand;

/**
 * Class WistiaVideoDialog.
 *
 * @package Drupal\wistia_integration\Form
 */
class WistiaVideoDialog extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wistia_video_dialog';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, FilterFormat $filter_format = NULL) {
    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#prefix'] = '<div id="add-video-editor-dialog-form">';
    $form['#suffix'] = '</div>';

    $form['wistia_chooser'] = [
      '#type' => 'inline_template',
      '#template' => '<div id="wistia-chooser" style="height: 400px; width: 360px;"></div>',
    ];

    $form['text'] = [
      '#type' => 'textfield',
      '#description' => $this->t('Video Id'),
      '#required' => TRUE,
      '#default_value' => '',
      '#attributes' => [
        'id' => 'wistia-video-url',
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['save_modal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#submit' => [],
      '#ajax' => [
        'callback' => '::submitForm',
        'event' => 'click',
      ],
    ];

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
      $response->addCommand(new HtmlCommand('#add-video-editor-dialog-form', $form));
    }
    else {
      $response->addCommand(new EditorDialogSave($form_state->getValues()));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

}
