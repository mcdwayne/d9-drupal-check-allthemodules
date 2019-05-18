<?php

namespace Drupal\mailjet_subscription\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use MailjetTools\MailjetApi;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\mailjet_subscription\Entity\SubscriptionForm;
use Drupal\user\Entity\User;
use Mailjet\Client;
use Mailjet\Resources;

/**
 * Subscribe to a Mailjet list.
 */
class SubscriptionSignupPageForm extends FormBase {

  /**
   * The ID for this form.
   * Set as class property so it can be overwritten as needed.
   *
   * @var string
   */
  private $formId = 'mailjet_signup_page_form';

  /**
   * The Mailjet Signup entity used to build this form.
   *
   * @var MailjetSignup
   */
  private $signup = NULL;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return $this->formId;
  }

  public function setFormID($formId) {
    $this->formId = $formId;
  }

  public function setSignupID($entity_Id) {
    $this->entity_id = $entity_Id;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mailjet_signup.page_form'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $mailjetApiClient = mailjet_new();
    $entity = mailjet_subscription_load($this->entity_id);


    $list_id = $entity->lists;
    $user = \Drupal::currentUser();
    $is_un_subs = 0;

    $filters = [
      'ContactEmail' => $user->getEmail(),
      'ContactsList' => $list_id,
    ];

    $result = $mailjetApiClient->get(Resources::$Listrecipient, ['filters' => $filters]);
    if ($result->success() && $result->getCount() > 0) {
      $data = $result->getData();
      if (isset($data[0]['IsUnsubscribed'])) {
        $is_un_subs = (true == $data[0]['IsUnsubscribed'] ? 0 : 1);
      }
    }

    if ($user->id() == 0 || ($user->id() !== 0 && $is_un_subs !== 1)) {

      $form['signup_id_form'] = [
        '#type' => 'hidden',
        '#value' => $this->entity_id,
      ];


      if (!empty($entity->description)) {
        $form['description'] = [
          '#markup' => t($entity->description),
        ];
      }


      $form['signup-email'] = [
        '#type' => 'textfield',
        '#title' => $entity->email_label,
        '#description' => 'Please enter your email address.',
        '#default_value' => '',
        '#required' => TRUE,
        '#attributes' => ['placeholder' => t('your@email.com')],
      ];

      $fields = [];
      $labels_fields = [];
      $fields_mailjet2 = [];
      $sort_config = explode(',', $entity->sort_fields);
      $fields_mailjet = explode(',', $entity->fields_mailjet);
      $labels = explode(',', $entity->labels_fields);

      $counter = 0;

      foreach ($fields_mailjet as $field) {
        $labels_fields[$field] = $labels[$counter];
        $counter++;
      }

      $counter = 0;
      foreach ($fields_mailjet as $field) {
        $fields_mailjet2[$field] = $fields_mailjet[$counter];
        $counter++;
      }

      $field_counter = 0;
      if (!(empty($sort_config[0]))) {
        foreach ($sort_config as $sort_field) {

          if (in_array(trim($sort_field), $fields_mailjet2) != FALSE) {
            $fields[$field_counter] = trim($sort_field);
          }

          $field_counter++;
        }

        $field_counter = 100;
        foreach ($fields_mailjet2 as $field) {

          if (!in_array(trim($field), $fields) != FALSE) {
            $fields[$field_counter] = trim($field);
          }

          $field_counter++;
        }
      }
      else {
        $fields = $fields_mailjet;
      }

      $counter = 0;

      if (!(empty($fields_mailjet[0]))) {

        foreach ($fields as $field) {

          switch ((mailjet_get_propertiy_type($field))) {
            case 'int':
              $description_field = t('Correct field format - numbers. Ex: 1234');
              break;

            case 'str':
              $description_field = t('Correct field format - text. Ex: First Name');
              break;

            case 'datetime':
              $description_field = t('Correct field format - date. Ex: 26-02-2010');
              break;

            case 'bool':
              $description_field = t('Correct field format - True or False. Ex: True');
              break;
          }

          $form['signup-' . $field] = [
            '#type' => 'textfield',
            '#title' => "" . $labels_fields[$field],
            '#description' => $description_field,
            '#default_value' => '',
            '#required' => TRUE,
          ];

          $counter++;
        }
      }

      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $entity->sumbit_label,
      ];
    }
    else {
      $form['signup_id_form'] = [
        '#type' => 'hidden',
        '#value' => $this->entity_id,
      ];

      $form['unsubscribe_id'] = [
        '#type' => 'hidden',
        '#value' => 1,
      ];


      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['unsubscribe'] = [
        '#type' => 'submit',
        '#weight' => 100000,
        '#value' => t('Unsubscribe'),
      ];
    }

    if (!empty($entity->js_field)) {
      $form['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'script',
          '#value' => htmlspecialchars_decode($entity->js_field),
        ],
        'mailjet_js',
      ];
    }

    if (!empty($entity->css_field)) {
      $form['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'style',
          '#value' => htmlspecialchars_decode($entity->css_field),
        ],
        'mailjet_css',
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $form_values = $form_state->getValues();
    $signup_form = mailjet_subscription_load($this->entity_id);

    if (!isset($form_values['unsubscribe_id']) && empty($form_values['unsubscribe_id'])) {
      if (!valid_email_address($form_values['signup-email'])) {
        $form_state->setErrorByName('signup-email', t('Please enter a valid email address!'));
      }

      $labels_fields = explode(',', $signup_form->labels_fields);
      $fields = explode(',', $signup_form->fields_mailjet);
      $counter = 0;

      if (!(empty($fields[0]))) {
        foreach ($fields as $field) {

          $field_value = $form_values['signup-' . $field];
          $field_name = $field;
          $missmatch_values = !empty($entity->error_data_types) ? $entity->error_data_types : 'Incorrect data values. Please enter correct data type in %id';

          $missmatch_values = str_replace("%id", $labels_fields[$counter], $missmatch_values);

          switch (mailjet_get_propertiy_type($field_name)) {
            case 'int':
              if (!preg_match('/^[0-9]{1,45}$/', $field_value) && !empty($field_value)) {
                $form_state->setErrorByName('signup-' . $field, $missmatch_values);
              }
              break;

            case 'str':
              if (!(is_string($field_value)) && !empty($field_value)) {
                $form_state->setErrorByName('signup-' . $field, $missmatch_values);
              }
              break;

            case 'datetime':

              if (!preg_match("/^\s*(3[01]|[12][0-9]|0?[1-9])\-(1[012]|0?[1-9])\-((?:19|20)\d{2})\s*$/", $field_value) && !empty($field_value)) {
                $form_state->setErrorByName('signup-' . $field, $missmatch_values);
              }
              else {
                if (!empty($field_value)) {
                  $date = $field_value;
                  $date_array = explode("-", $date);

                  if (checkdate($date_array[1], $date_array[0], $date_array[2]) == FALSE) {
                    $form_state->setErrorByName('signup-' . $field, $missmatch_values);
                  }
                }
              }

              break;

            case 'bool':
              if (!(strtoupper($field_value) == 'TRUE' || strtoupper($field_value) == 'FALSE') && !empty($field_value)) {
                $form_state->setErrorByName('signup-' . $field, $missmatch_values);
              }
              break;
          }

          $counter++;
        }
      }
    }
  }

  private function manageFields($mailjetApiClient, $entity, $form_values, $contact_id) {
    $data = [];
    $fields = explode(',', $entity->fields_mailjet);

    // Collect data from filled fields
    if (!(empty($fields[0]))) {
      foreach ($fields as $field) {
        if (!empty($field) && !empty($form_values['signup-' . $field])) {
          switch (mailjet_get_propertiy_type($field)) {
            case 'datetime':
              $data_value = \DateTime::createFromFormat('d-m-Y', trim($form_values['signup-' . $field]))
                ->getTimestamp();
              break;

            default:
              $data_value = $form_values['signup-' . $field];
              break;
          }

          $data[] = [
            'Name' => $field,
            'Value' => $data_value,
          ];
        }
      }
    }

    if (empty($data)) {
      // There is no contact data
      return TRUE;
    }

    $body = [
      'ContactID' => $contact_id,
      'Data' => $data
    ];

    $response = $mailjetApiClient->put(Resources::$Contactdata, ['id' => $contact_id, 'body' => $body]);
    if (!$response->success()) {
      $start = '[{ "';
      $end = '" :';
      $ini = strpos($response->getErrorMessage(), $start);
      $ini += strlen($start);
      $len = strpos($response->getErrorMessage(), $end, $ini) - $ini;
      $filed_prop_name = trim(substr($response->getErrorMessage(), $ini, $len));
      $missmatch_values = !empty($entity->error_data_types) ? $entity->error_data_types : 'Incorrect data values. Please enter correct data type in %id';
      $missmatch_values = str_replace("%id", $filed_prop_name, $missmatch_values);

      switch (mailjet_get_propertiy_type($filed_prop_name)) {
        case 'int':
          drupal_set_message($missmatch_values, 'error');
          break;

        case 'str':
          drupal_set_message($missmatch_values, 'error');
          break;

        case 'datetime':
          drupal_set_message($missmatch_values, 'error');
          break;

        case 'bool':
          drupal_set_message($missmatch_values, 'error');
          break;
      }
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    global $base_url;
    $form_values = $form_state->getValues();
    $entity = mailjet_subscription_load($form_values['signup_id_form']);
    $email_text_button = !empty($entity->email_text_button) ? $entity->email_text_button : t('Click here to confirm');
    $email_text_description = !empty($entity->email_text_description) ? $entity->email_text_description : t('You may copy/paste this link into your browser:');
    $email_text_thank_you = !empty($entity->email_text_thank_you) ? $entity->email_text_thank_you : t('Thanks,');
    $owner = !empty($entity->email_owner) ? $entity->email_owner : t('Mailjet');
    $email_footer_text = !empty($entity->email_footer_text) ? $entity->email_footer_text : t('Did not ask to subscribe to this list? Or maybe you have changed your mind? Then simply ignore this email and you will not be subscribed');
    $email = $form_values['signup-email'];
    $heading_text = !empty($entity->confirmation_email_text) ? $entity->confirmation_email_text : t('Please confirm your subscription to');

    $list_id = $entity->lists;

    $properties = $this->fetchPropertiesOnWidgetSubscribe($form_state);

    $user = \Drupal::currentUser();
    $mailjetApiClient = mailjet_new();
//    $check_complate = FALSE;

    //Unsubscribe
    if (!empty($form_values['unsubscribe_id'])) {
//      $url = 'http://api.mailjet.com/v3/REST/user/' . $user->getEmail();
//      $result = $mailjetApiClient->generalRequest(FALSE, [], 'GET', $url);
//      $result_arr = json_decode($result);
//      $user_id = $result_arr->Data[0]->ID;
        
      $responseContactList = MailjetApi::syncMailjetContact($list_id, ['Email' => $user->getEmail()], 'unsub');
      
//echo $list_id;
//echo "<pre>";
//var_dump($response);
//exit;
      if (false != $responseContactList) {
        \Drupal::logger('mailjet_messages')
          ->error(t('The contact was unsubscribed from list #' . $list_id . '.'));
        drupal_set_message(t('The contact was unsubscribed successfully!'));
        $response = new RedirectResponse($base_url);
        $response->send();
        return;
      }
      else {
        \Drupal::logger('mailjet_messages')
          ->error(t('The contact was not unsubscribed from list #' . $list_id . '.'));
        drupal_set_message(t('Error'), 'error');
      }
      return;
    }

    $user_exists = mailjet_find_conctact($email, $list_id);

    // The user exists in the given list
    if ($user_exists) {
        $message = str_replace('%', $email, $entity->contact_exist);
        drupal_set_message($message, 'error');
        return;
    }

    $langcode = \Drupal::currentUser()->getPreferredLangcode();

    $query_string = http_build_query(array(
        'sec_code' => base64_encode($email),
        'list' => $list_id,
        'properties' => base64_encode(json_encode($properties, true)),
        'others' => $form_values['signup_id_form'],
    ));
    $subscribe_url = $base_url . '/confirmation-subscribe?' . $query_string;
    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = 'mailjet';
    $key = 'activation_mail';
    $to = $email;
    $params['message'] = prepare_mail_template($heading_text, $email_text_button, $email_text_description, $email_text_thank_you, $owner, $subscribe_url, $email_footer_text);
    if ($mailManager->mail($module, $key, $to, $langcode, $params, NULL, TRUE)) {
      $confirmation_message = str_replace("%", $email, $entity->confirmation_message);
      if (!empty($entity->confirmation_message)) {
        drupal_set_message(t($confirmation_message), 'status');
      }
      else {
        drupal_set_message(t('Subscription confirmation email was sent to ' . $email . '. Please check your inbox and confirm the subscription.'));
      }
    }

    //redicrect or redicrect and display success message after all process
    if (!empty($entity->destination_page)) {
      //redirect or redirect and display success message
      $subscribe_url = $entity->destination_page;

      $response = new RedirectResponse($subscribe_url);
      $response->send();
      return;
    }

  }


  private function fetchPropertiesOnWidgetSubscribe(FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    $properties = [];
    // We loop all form values to find contact properties.
    foreach ($form_values as $key => $value) {
      if ($key != 'signup-email' && stristr($key, 'signup-')) {
        $properties[$key] = $value;
      }
    }
    return $properties;
  }
}
