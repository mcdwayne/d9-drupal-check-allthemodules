<?php

namespace Drupal\file_utility\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Language\Language;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;

/**
 * Form controller for the content_entity_example entity edit forms.
 *
 * @ingroup content_entity_example
 */
class FileUtilityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\content_entity_example\Entity\Contact */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;
    $user_ip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : \Drupal::request()->getHost();
    $f_path_encoded = \Drupal::request()->query->get('f_path');
    $f_path = base64_decode(urldecode($f_path_encoded));
    $uri_arr = explode('/', $f_path);
    $file_name = end($uri_arr);
    // Load File Enitity by filename and get fid.
    $result = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['filename' => $file_name]);
    if ($result) {
      $file_object = reset($result);
      $form['fid']['widget'][0]['target_id']['#default_value'] = $file_object;
    }

    $form['langcode'] = [
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->getId(),
      '#languages' => Language::STATE_ALL,
    ];

    $form['#prefix'] = '<div id="status_message_div">';
    $form['#suffix'] = '</div>';

    // The status messages that will contain any form errors.
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];
    // Set default value for different fields.
    $form['file_path']['widget'][0]['value']['#default_value'] = $f_path;
    $form['ip_address']['widget'][0]['value']['#default_value'] = $user_ip;
    $form['count']['widget'][0]['value']['#default_value'] = 1;

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#attributes' => [
        'class' => [
          'use-ajax',
        ],
      ],
      '#ajax' => [
        'callback' => [$this, 'submitUserInfoFormAjax'],
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * AJAX callback handler that displays any errors or a success message.
   */
  public function submitUserInfoFormAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $value = $form_state->getValues();
    $name = $value['name'];
    $email = $value['email'];
    $user_ip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : \Drupal::request()->getHost();
    $f_path_encoded = \Drupal::request()->query->get('f_path');
    $f_path = base64_decode(urldecode($f_path_encoded));
    $force_download = \Drupal::request()->query->get('force_download');
    // If there are any form errors, re-display the form.
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#status_message_div', $form));
    }
    else {
      $status = parent::save($form, $form_state);

      $entity = $this->entity;
      $response->addCommand(new RedirectCommand('/filedownload?f_path=' . $f_path_encoded . '&force_download=' . $force_download));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

  }

}
