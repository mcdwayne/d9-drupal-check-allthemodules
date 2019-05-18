<?php

namespace Drupal\mailcamp\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\mailcamp\MailcampService;

/**
 * Provides a 'MailcampSignup' block.
 *
 * @Block(
 *  id = "mailcamp_signup",
 *  admin_label = @Translation("Mailcamp signup"),
 * )
 */
class MailcampSignup extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'submit_button_label' => $this->t('Submit'),
      'confirmation_message' => $this->t('You have been successfully subscribed.'),
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $mailcamp = new MailcampService();
    $names = $mailcamp->getMailingListNames();

    if (!is_array($names)) {
      $url = Url::fromRoute('mailcamp.settings_form');
      drupal_set_message($this->t('API call was unsuccessful. Have you <a href=":url">configured proper credentials</a>?', [':url' => $url->toString()]), 'warning');
    }
    $form['description'] = [
      '#type' => 'text_format',
      '#default_value' => $this->configuration['description']['value'],
      '#format' => $this->configuration['description']['format'],
      '#description' => $this->t('This description will be show above the signup form.'),
      '#title' => $this->t('Description'),
    ];
    $form['submit_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Submit Button Label'),
      '#default_value' => $this->configuration['submit_button_label'],
      '#required' => TRUE,
    ];
    $form['confirmation_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Confirmation Message'),
      '#description' => $this->t('This message will appear after a successful submission of this form. Leave blank for no message.'),
      '#default_value' => $this->configuration['confirmation_message'],
    ];
    $form['form_destination_page'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form destination page'),
      '#description' => $this->t('Leave blank to stay on the form page.'),
      '#default_value' => $this->configuration['form_destination_page'],
    ];
    $form['mailcamp_lists'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#title' => $this->t('Mailcamp lists'),
      '#description' => $this->t('Select which lists to show on the signup form.'),
      '#default_value' => $this->configuration['mailcamp_lists'],
      '#options' => $names,
      '#size' => 10,
      '#ajax' => [
        'callback' => [$this, 'listsChange'],
        'wrapper' => 'customfields-wrapper',
      ],
    ];
    $form['customfields_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'customfields-wrapper'],
    ];
    if ($form_state->isRebuilding()) {
      $lists = $form_state->getUserInput()['settings']['mailcamp_lists'];
    }
    else {
      $lists = $this->configuration['mailcamp_lists'];
    }

    if ($lists) {
      $form['customfields_wrapper']['mailcamp_customfields'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Custom fields'),
        '#description' => $this->t('Select which fields to show on your signup form.'),
        '#options' => $mailcamp->getCustomFieldNames($lists),
        '#default_value' => $this->configuration['mailcamp_customfields'],
      ];
    }

    return $form;
  }

  /**
   * Ajax callback for when the user selects a list.
   */
  public function listsChange(array &$form, FormStateInterface $form_state) {
    return $form['settings']['customfields_wrapper'];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['submit_button_label'] = $form_state->getValue('submit_button_label');
    $this->configuration['confirmation_message'] = $form_state->getValue('confirmation_message');
    $this->configuration['form_destination_page'] = $form_state->getValue('form_destination_page');
    $this->configuration['mailcamp_lists'] = $form_state->getValue('mailcamp_lists');
    $this->configuration['description'] = $form_state->getValue('description');

    $this->configuration['mailcamp_customfields'] = $form_state->getValue('customfields_wrapper')['mailcamp_customfields'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\mailcamp\Form\SignupForm', $this->configuration);

    return $form;
  }

}
