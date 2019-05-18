<?php

namespace Drupal\getresponse_forms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\getresponse_forms\Entity\GetresponseForms;
use Drupal\getresponse\Service\Api;

/**
 * Subscribe to a GetResponse list.
 */
class GetresponseFormsPageForm extends FormBase {

  /**
   * The ID for this form.
   * Set as class property so it can be overwritten as needed.
   *
   * @var string
   */
  private $formId = 'getresponse_forms_page_form';

  /**
   * The GetresponseForms entity used to build this form.
   *
   * @var GetresponseForms
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

  public function setSignup(GetresponseForms $signup) {
    $this->signup = $signup;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['getresponse_forms.page_form'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = array();

    $form['#attributes'] = array('class' => array('getresponse-forms-subscribe-form'));

    $form['description'] = array(
      '#markup' => $this->signup->description,
      '#weight' => -80,
    );

    foreach ($this->signup->getFields() as $field) {
      getresponse_forms_drupal_form_element($field, $form, $form_state);
    }

    $lists = getresponse_get_lists([$this->signup->gr_lists]);
    if (empty($lists)) {
      drupal_set_message($this->t('The subscription service is currently unavailable. Please try again later.'), 'warning');
    }

    $list = reset($lists);
    $form['getresponse_lists'] = array(
      '#type' => 'hidden',
      '#title' => $list->name,
      '#value' => $list->campaignId,
      '#weight' => 80,
    );

    $form['actions'] = ['#type' => 'actions', '#weight' => 99];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->signup->submit_button,
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $build_info = $form_state->getBuildInfo();
    $signup = $build_info['callback_object']->signup;

    // Ensure that we have an e-mail address.
    $email = $form_state->getValue('getresponse_forms_email_field');
    if (!$email) {
      // TODO make sure we don't ever show them this when there is no form.
      $form_state->setErrorByName('getresponse_forms_email_field', t("Please enter your e-mail address."));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    global $base_url;

    $getresponse_lists = $form_state->getValue('getresponse_lists');

    $list_id = $this->signup->gr_lists;

    $request = [
      "name" => $form_state->getValue('getresponse_forms_name_field'),
      "email" => $form_state->getValue('getresponse_forms_email_field'),
      "campaign" => [
        "campaignId" => $list_id,
      ],
      "customFieldValues" => [],
    ];

    foreach ($this->signup->getFields() as $field) {
      $definition = $field->getPluginDefinition();
      if (isset($definition['customFieldId'])) {
        $key = $definition['name'];
        $value = $form_state->getValue($key);
        if ($value) {
          $arrayed_value = is_array($value) ? $value : [$value];
          // For any select field we must replace the key with the real value.
          $gr_fields = getresponse_get_custom_fields();
          if (isset($gr_fields[$definition['customFieldId']])) {
            $field = $gr_fields[$definition['customFieldId']];
          }
          if (in_array($field->fieldType, ['single_select', 'radio', 'checkbox',])) {
            $prepped_value = [];
            $single_checkbox = FALSE;
            if (isset($form[$key]['#options'])) {
              $options = $form[$key]['#options'];
            }
            else {
              // Single checkboxes do not have values in '#options' *and* they
              // need to be given an empty zero value and have their '0' result
              // used for '1'.
              if (count($field->values) === 1) {
                $single_checkbox = TRUE;
                $value = array_pop($field->values);
                $options = ['0' => '', '1' => $value];
              }
              else {
                \Drupal::logger('getresponse_forms')->error('Unexpected number of options {options} for field {field}', ['options' => var_export($field->value, TRUE), 'field' => var_export($form[$key], TRUE)]);
              }
            }
            foreach ($arrayed_value as $val) {
              // The real results from a form are always string, thus we want to
              // keep '0' and throw out 0.
              if (gettype($val) === 'string') {
                $prepped_value[] = $options[$val];
              }
              else {
                // Except for single checkbox forms apparently!?!
                if ($single_checkbox) {
                  $prepped_value[] = $options[$val];
                }
              }
            }
          }
          else {
            $prepped_value = $arrayed_value;
          }

          $request["customFieldValues"][] = [
            "customFieldId" => $definition['customFieldId'],
            "value" => $prepped_value,
          ];
        }
      }
    }

    $api_key = \Drupal::config('getresponse.settings')->get('api_key');
    $api     = new Api($api_key);
    $result  = $api->addContact($request);

    $lists = getresponse_get_lists([$list_id]);
    $list = reset($lists);
    if (isset($result->httpStatus) && $result->httpStatus >= 400) {
      drupal_set_message(t('There was a problem with your newsletter signup to %list.',
        ['%list' => $list->name]), 'warning');

      \Drupal::logger('getresponse_forms')->error('An error occurred while creating contact with request: {request}.  GetResponse responded: {result}', ['request' => var_export($request, TRUE), 'result' => var_export($result, TRUE)]);
    }
    else {
      if (strlen($this->signup->confirmation_message)) {
        drupal_set_message($this->signup->confirmation_message, 'status');
      }
    }

    $destination = $this->signup->destination;
    if (empty($destination)) {
      $destination_url = Url::fromRoute('<current>');
    }
    else {
      $destination_url = Url::fromUri($base_url . '/' . $this->signup->destination);
    }

    $form_state->setRedirectUrl($destination_url);
  }

}
