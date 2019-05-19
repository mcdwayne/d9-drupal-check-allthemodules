<?php

namespace Drupal\webform_to_leads\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class WebToLeadConfigForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'web_to_lead_configuration';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'webform_to_leads.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['state'] = ["#type" => "hidden", "#value" => "table"];
    $config = $this->config('webform_to_leads.settings');
    $settings = $config->get();
    $form['salesforce_oid'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Salesforce OID'),
      '#default_value' => (isset($settings['salesforce_oid']) ? $settings['salesforce_oid'] : ""),
      "#description" => "The OID value (shorthand for Organizational Wide Default) 
      is your instance of Salesforce.com. That doesn’t show up in very many places, 
      but if you navigate to Setup > Administration Setup > Company Profile > 
      Company Information – you’ll see your OID listed as a field on that page as well.",
    );

    $form['salesforce_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Salesforce URL'),
      '#default_value' => (isset($settings['salesforce_url']) ? $settings['salesforce_url'] : "https://webto.salesforce.com/servlet/servlet.WebToLead"),
    );

    $form['debug'] = array(
      "#type" => "checkbox",
      "#title" => "DEBUG MODE",
      '#default_value' => (isset($settings['debug']) ? $settings['debug'] : 0),
    );
    $form['debug_email'] = array(
      "#type" => "textfield",
      "#title" => "Debug email.",
      '#states' => array(
        'visible' => array(
          ':input[name="debug"]' => array('checked' => TRUE),
        ),
      ),
      '#default_value' => (isset($settings['debug_email']) ? $settings['debug_email'] : ""),
    );

    $form['#tree'] = TRUE;

    $form['field_wrapper'] = [
      '#type' => "fieldset",
      '#title' => "Webform Fields to Salesforce Fields Mapping Table",
      "#description" => "Legend: KEY is the Webform Field Key, VALUE is the Salesforce Web-To-Lead form field",
      '#attributes' => [
        'id' => 'fields-wrapper',
      ],
      '#states' => array(
        'visible' => array(
          ':input[name="state"]' => array('value' => "table"),
        ),
      ),
    ];

    $form['field_wrapper']['field_table'] = array(
      '#type' => 'table',
      '#header' => array(t('KEY'), t('VALUE')),
      '#attributes' => [
        'id' => ['fields-table'],
      ],
    );
    $default_value_textarea = "";
    $count = 0;
    if (isset($settings['fields'])) {
      foreach ($settings['fields'] as $i => $field) {
        foreach ($field as $key => $value) {
          $form['field_wrapper']['field_table'][$i] = $this->getTableLine($i, [$key, $value]);
          $default_value_textarea .= $key . "|" . $value . "\n";
        }
      }
      $count = count($settings['fields']);
    }

    $count++;
    $form['field_wrapper']['field_table'][$count] = $this->getTableLine($count);
    $count++;
    // Build the extra lines
    $triggeringElement = $form_state->getTriggeringElement();
    $clickCounter = 0;
    // if a click occurs
    if ($triggeringElement) {
      if (isset($triggeringElement['#attributes']['id']) && $triggeringElement['#attributes']['id'] == 'add-row') {
        // $formstate and $form element are updated
        // click counter is incremented
        if ($form_state->hasValue("click_counter")) {
          $clickCounter = $form_state->getValue('click_counter');
        }
        $clickCounter++;
        $form_state->setValue('click_counter', $clickCounter);
        $form['click_counter'] = array('#type' => 'hidden', '#default_value' => 0, '#value' => $clickCounter);
      }
      if (isset($triggeringElement['#attributes']['id']) && $triggeringElement['#attributes']['id'] == "change-style") {
        if ($form_state->hasValue("state")) {
          $state = $form_state->getValue('state');
          if ($state != "table") {
            $state = "table";
          }
          else {
            $state = "textarea";
          }

        }
        $form['state'] = ["#type" => "hidden", "#value" => $state];
      }
    }
    else {
      $form['click_counter'] = array('#type' => 'hidden', '#default_value' => 0);
    }
    // Build the extra table rows and columns.
    for ($k = 0; $k < $clickCounter; $k++) {
      $form['field_wrapper']['field_table'][$count + $k] = $this->getTableLine($count + $k);
    }

    $form['field_wrapper_textarea'] = [
      '#type' => "fieldset",
      '#title' => "Webform Fields to Salesforce Fields Mapping",
      '#states' => array(
        'visible' => array(
          ':input[name="state"]' => array('value' => "textarea"),
        ),
      ),
    ];
    $form['field_wrapper_textarea']['textarea'] = array(
      "#type" => "textarea",
      "#default_value" => $default_value_textarea,
      "#description" => "List options one option per line. Key-value pairs may be specified by separating each option with pipes, such as key|value.",
      "#rows" => 30
    );

    $form['field_wrapper']['add_field'] = array(
      '#type' => 'submit',
      '#value' => t('Add one more'),
      '#attributes' => array(
        'id' => 'add-row'
      ),
    );
    $form['change_input_style'] = array(
      '#type' => "submit",
      "#value" => t("Change Input Style"),
      '#attributes' => array(
        'id' => 'change-style'
      ),
    );

    return parent::buildForm($form, $form_state);
  }


  public function validateForm(array &$form, FormStateInterface $form_state) {
    $triggeringElement = $form_state->getTriggeringElement();
    if ($triggeringElement && isset($triggeringElement['#attributes']['id']) &&
      ($triggeringElement['#attributes']['id'] == 'add-row' || $triggeringElement['#attributes']['id'] == "change-style")
    ) {
      $form_state->setRebuild();
    }
  }


  public function getTableLine($i, $defaultValue = []) {
    $line = array();
    $line['key'] = array(
      '#type' => 'textfield',
      '#limit' => 150,
      "#default_value" => (isset($defaultValue[0]) ? $defaultValue[0] : "")
    );
    $line['value'] = array(
      '#type' => 'textfield',
      '#limit' => 150,
      "#default_value" => (isset($defaultValue[1]) ? $defaultValue[1] : "")
    );
    return $line;
  }

  /**
   * {@inheritdoc}
   */
  public
  function submitForm(array &$form, FormStateInterface $form_state) {
    $triggeringElement = $form_state->getTriggeringElement();

    if (isset($triggeringElement['#id']) && $triggeringElement['#id'] == "edit-actions-submit") {
      $style = $form_state->getValue("state");
      $values = $form_state->getValues();
      $fields = array();
      if ($style == "table") {
        foreach ($values['field_wrapper']['field_table'] as $value) {
          if (!empty($value['key']) && !empty($value['value'])) {
            $fields[] = [$value['key'] => $value['value']];
          }
        }
      }
      else {
        $text_array = explode("\n", trim($values['field_wrapper_textarea']['textarea']));
        foreach ($text_array as $line) {
          $key_value = explode("|", $line);
          $fields[] = [$key_value[0] => $key_value[1]];
        }
      }

      // Retrieve the configuration
      $this->configFactory->getEditable('webform_to_leads.settings')
        ->set('salesforce_oid', $form_state->getValue('salesforce_oid'))
        ->set('salesforce_url', $form_state->getValue('salesforce_url'))
        ->set('debug', $form_state->getValue('debug'))
        ->set('debug_email', $form_state->getValue('debug_email'))
        ->set('fields', $fields)
        ->save();

      parent::submitForm($form, $form_state);
    }
  }
}
