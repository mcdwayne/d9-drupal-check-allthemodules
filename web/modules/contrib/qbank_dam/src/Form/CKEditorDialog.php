<?php

namespace Drupal\qbank_dam\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\qbank_dam\QBankDAMService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CKEditorDialog.
 *
 * @package Drupal\qbank_dam\Form
 */
class CKEditorDialog extends FormBase {

  protected $QAPI;

  public function __construct(QBankDAMService $qbank_api) {
    $this->QAPI = $qbank_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('qbank_dam.service'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'qbank_ckeditor_dialog';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Ensure relevant dialog libraries are attached.
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#attached']['library'][] = 'qbank_dam/ckeditor';

    $wrapper_id = 'qbank-ckeditor-wrapper' . rand();
    $form['#attached']['drupalSettings']['qbank_dam']['protocol'] = $this->QAPI->getProtocol();
    $form['#attached']['drupalSettings']['qbank_dam']['deployment_site'] = $this->QAPI->getDeploymentSite();
    $form['#attached']['drupalSettings']['qbank_dam']['url'] = $this->QAPI->getApiUrl();
    $form['#attached']['drupalSettings']['qbank_dam']['token'] = $this->QAPI->getToken();
    $form['#attached']['drupalSettings']['qbank_dam']['modulePath'] = drupal_get_path('module', 'qbank_dam');
    $form['#attached']['drupalSettings']['qbank_dam']['html_id'] = $wrapper_id;

    $form['#prefix'] = '<div id="' . $wrapper_id . '">';
    $form['#suffix'] = '</div>';

    $form['qbank_url'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Url'),
      '#maxlength' => 256,
      '#size' => 64,
    ];

    $form['qbank_extension'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Extension'),
      '#maxlength' => 256,
      '#size' => 64,
    ];

    $form['qbank_title'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Title'),
      '#maxlength' => 64,
      '#size' => 64,
    ];

    $form['qbank_media_id'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Media ID'),
      '#maxlength' => 64,
      '#size' => 64,
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
        'wrapper' => 'qbank-dam-dialog-form',
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
    $response = new AjaxResponse();
    $file = $this->QAPI->download(
      $form_state->getValue('qbank_url'),
      $form_state->getValue('qbank_media_id')
    );

    if (!empty($file)) {
      $file_url = file_create_url($file->getFileUri());
      // Transform absolute file URLs to relative file URLs: prevent problems
      // on multisite set-ups and prevent mixed content errors.
      $file_url = file_url_transform_relative($file_url);
      $form_state->setValue(array('attributes', 'src'), $file_url);
      $form_state->setValue(array('attributes', 'data-entity-uuid'), $file->uuid());
      $form_state->setValue(array('attributes', 'data-entity-type'), 'file');
    }

    if ($form_state->getErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#editor-file-dialog-form', $form));
    }
    else {
      $response->addCommand(new EditorDialogSave($form_state->getValues()));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }
}
