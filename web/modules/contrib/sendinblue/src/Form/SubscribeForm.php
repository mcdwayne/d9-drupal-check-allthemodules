<?php

namespace Drupal\sendinblue\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\sendinblue\SendinblueManager;

/**
 * Subscribe form to signup SendinBlue newsletter.
 */
class SubscribeForm extends FormBase {
  public $signupIp;

  /**
   * Constructor for ComproCustomForm.
   *
   * @param int $derivativeId
   *   The ID of signupForm.
   */
  public function __construct($derivativeId) {
    $this->signupIp = $derivativeId;
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'sendinblue_form_subscribe';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param int $mcsId
   *   The ID of signupForm.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $mcsId = NULL) {
    if ($mcsId) {
      $this->signupIp = $mcsId;
    }

    $entity_manager = \Drupal::entityTypeManager();
    $signup = $entity_manager->getStorage(SENDINBLUE_SIGNUP_ENTITY)
      ->load($this->signupIp);
    $settings = (!$signup->settings->first()) ? [] : $signup->settings->first()
      ->getValue();

    $form['#attributes'] = ['class' => ['sendinblue-signup-subscribe-form']];
    $form['description'] = [
      '#plain_text' => $settings['description'],
    ];

    $form['fields'] = [
      '#prefix' => '<div id="sendinblue-newsletter-' . ($settings['subscription']['settings']['list']) . '-mergefields" class="sendinblue-newsletter-mergefields">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    ];

    if (isset($settings['fields']['mergefields'])) {
      $merge_fields = $settings['fields']['mergefields'];
      $attributes = SendinblueManager::getAttributeLists();

      if (is_array($merge_fields)) {
        foreach ($merge_fields as $key => $value) {
          if ($key == 'EMAIL') {
            $form['fields'][$key] = [
              '#type' => 'textfield',
              '#title' => ($value['label']),
              '#attributes' => ['style' => 'width:100%;box-sizing:border-box;'],
              '#required' => TRUE,
            ];
          }
          else {
            if (isset($value['check']) && $value['required']) {
              foreach ($attributes as $attribute) {
                if ($attribute['name'] == $key) {
                  $type = $attribute['type'];
                  if ($type == 'category') {
                    $enumerations = $attribute['enumeration'];
                  }
                  break;
                }
              }
              if ($type != 'category') {
                $form['fields'][$key] = [
                  '#type' => 'textfield',
                  '#title' => ($value['label']),
                  '#attributes' => ['style' => 'width:100%;box-sizing:border-box;'],
                  '#required' => isset($value['required']) && $value['required'] ? TRUE : FALSE,
                ];
              }
              else {
                $options = [];
                foreach ($enumerations as $enumeration) {
                  $options[$enumeration['value']] = $enumeration['label'];
                }
                $form['fields'][$key] = [
                  '#type' => 'select',
                  '#title' => ($value['label']),
                  '#options' => $options,
                  '#attributes' => ['style' => 'width:100%;box-sizing:border-box;'],
                  '#required' => isset($value['required']) ? TRUE : FALSE,
                ];
              }
            }
          }
        }
      }
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#weight' => 10,
      '#value' => ($settings['fields']['submit_button']),
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entity_manager = \Drupal::entityTypeManager();
    $signup = $entity_manager->getStorage(SENDINBLUE_SIGNUP_ENTITY)
      ->load($this->signupIp);
    $settings = (!$signup->settings->first()) ? [] : $signup->settings->first()
      ->getValue();
    $email_validator = \Drupal::service('email.validator');

    $email = $form_state->getValue(['fields', 'EMAIL']);
    $list_id = $settings['subscription']['settings']['list'];

    if (!$email_validator->isValid($email)) {
      $form_state->setErrorByName('email', $settings['subscription']['messages']['invalid']);
      return;
    }

    $response = SendinblueManager::validationEmail($email, $list_id);
    if ($response['code'] == 'invalid') {
      $form_state->setErrorByName('email', $settings['subscription']['messages']['invalid']);
      return;
    }
    if ($response['code'] == 'already_exist') {
      $form_state->setErrorByName('email', $settings['subscription']['messages']['existing']);
      return;
    }

    $email_confirmation = $settings['subscription']['settings']['email_confirmation'];
    if ($email_confirmation == '1') {
      $templage_id = $settings['subscription']['settings']['template'];
    }

    $list_ids = $response['listid'];
    array_push($list_ids, $list_id);

    $info = [];
    $attributes = SendinblueManager::getAttributeLists();

    foreach ($attributes as $attribute) {
      $field_attribute_name = $form_state->getValue([
        'fields',
        $attribute['name'],
      ]);
      if (isset($field_attribute_name)) {
        $info[$attribute['name']] = $form_state->getValue([
          'fields',
          $attribute['name'],
        ]);
      }
    }
    $response_code = SendinblueManager::subscribeUser($email, $info, $list_ids);
    if ($response_code != 'success') {
      $form_state->setErrorByName('email', $settings['subscription']['messages']['general']);
      return;
    }

    // Store db.
    $data = SendinblueManager::getSubscriberByEmail($email);
    if ($data == FALSE) {
      $uniqid = uniqid();
      $data = [
        'email' => $email,
        'info' => serialize($info),
        'code' => $uniqid,
        'is_active' => 1,
      ];
      SendinblueManager::addSubscriberTable($data);
    }
    else {
      $uniqid = $data['code'];
    }

    // Send confirm email.
    if ($email_confirmation == '1') {
      SendinblueManager::sendEmail('confirm', $email, $uniqid, $list_id, '-1', $templage_id);
    }
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity_manager = \Drupal::entityTypeManager();
    $signup = $entity_manager->getStorage(SENDINBLUE_SIGNUP_ENTITY)
      ->load($this->signupIp);
    $settings = (!$signup->settings->first()) ? [] : $signup->settings->first()
      ->getValue();

    drupal_set_message(($settings['subscription']['messages']['success']));

    if ($settings['subscription']['settings']['redirect_url'] != '') {
      $form_state->setRedirectUrl(Url::fromUri('internal:/' . $settings['subscription']['settings']['redirect_url']));
    }
  }

}
