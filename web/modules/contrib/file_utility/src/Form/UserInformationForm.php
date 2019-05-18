<?php

namespace Drupal\file_utility\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;

/**
 * UserInformationForm class.
 */
class UserInformationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_information_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="user_information">';
    $form['#suffix'] = '</div>';

    // The status messages that will contain any form errors.
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#name' => 'name',
      '#title' => $this->t('Name'),
      '#default_value' => '',
      '#required' => TRUE,
    ];

    $form['email'] = [
      '#type' => 'textfield',
      '#name' => 'email',
      '#title' => $this->t('Email Address'),
      '#default_value' => '',
      '#required' => TRUE,
    ];

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
        'callback' => [$this, 'submitUserFormAjax'],
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * AJAX callback handler that displays any errors or a success message.
   */
  public function submitUserFormAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $value = $form_state->getValues();
    $name = $value['name'];
    $email = $value['email'];
    $user_ip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : \Drupal::request()->getHost();
    $f_path = \Drupal::request()->query->get('f_path');
    $modal = \Drupal::request()->query->get('modal');
    if ($modal == '1') {
      $file_link = '<a href="/filedownload?f_path=' . $f_path . '">Click to Download</a>';
    }
    else {
      $file_link = '<a href="' . $f_path . '">Click to Download</a>';
    }
    // If there are any form errors, re-display the form.
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#user_information', $form));
    }
    else {
      $fields = [
        'name'   => $name,
        'email' => $email,
        'file_path' => $f_path,
        'ip_address' => $user_ip,
        'count' => 1,
        'created' => time(),
      ];
      $query = \Drupal::database();
      $result = $query->query("SELECT id, count FROM file_downbload_users WHERE email = '" . $email . "' AND file_path = '" . $f_path . "'")->fetchAssoc();
      if (!empty($result)) {
        $id = $result['id'];
        $fields['count'] = $result['count'] + 1;
        $query = \Drupal::database();
        $query->update('file_downbload_users')
          ->fields($fields)
          ->condition('id', $id)
          ->execute();
      }
      else {
        $query = \Drupal::database();
        $query->insert('file_downbload_users')
          ->fields($fields)
          ->execute();
      }

      $response->addCommand(new RedirectCommand('/filedownload?f_path=' . $f_path));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email');
    $is_valid_email = \Drupal::service('email.validator')->isValid($email);
    if (empty($is_valid_email)) {
      $form_state->setErrorByName('email', $this->t('Please enter valid Email Address.'));
    }
  }

  /**
   * Submit handler of the config Form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
