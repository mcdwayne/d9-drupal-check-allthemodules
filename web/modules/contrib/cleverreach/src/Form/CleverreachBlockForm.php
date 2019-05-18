<?php

namespace Drupal\cleverreach\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class CleverreachBlockForm extends FormBase {
  
  protected $block_id;
  
  public function __construct($block_id) {
    $this->block_id = substr($block_id, 18);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cleverreach_block_form_' . $this->block_id;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $db = \Drupal::database();
    $form['#tree'] = TRUE;
    $form['cr_form_wrapper'] = array(
      '#markup' => '',
    );
    $form['cr_form_wrapper']['cr_block_bid'] = array(
      '#type' => 'hidden',
      '#value' => $this->block_id,
    );
    $form['cr_form_wrapper'][$this->block_id]['cr_block_mail_' . $this->block_id] = array(
      '#type' => 'textfield',
      '#title' => $this->t('E-Mail:'),
      '#required' => 1,
      '#size' => 25,
    );
    $fields = $db->query('SELECT bf.fields FROM {cleverreach_block_forms} bf WHERE bf.bid = :bid', array(':bid' => $this->block_id))->fetchField();
    $un_fields = unserialize($fields);

    if (count($un_fields) > 0 && !empty($un_fields)) {

      foreach ($un_fields as $value) {

        if ($value["active"] == 1) {
          $form['cr_form_wrapper'][$this->block_id]['cr_block_' . $value["name"] . '_' . $this->block_id] = $this->build_block_field($value);
        }

      }

    }

    $form['cr_form_wrapper'][$this->block_id]['cr_block_submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('subscribe'),
    );
    return $form;
  }
  
  /**
   * Function for buildung the blockform fields. 
   */
  public function build_block_field($field_data) {
    $field = array(
      '#title' => ucfirst($field_data["label"]) . ":",
      '#required' => $field_data["required"],
    );

    if ($field_data["display"] == "textfield") {
      $field['#type'] = 'textfield';
      $field['#size'] = 25;
      $field['#default_value'] = isset($field_data["display_options"]) ? $field_data["display_options"] : '';
    }

    elseif ($field_data["display"] == "select") {
      $field['#type'] = 'select';
      $field['#multiple'] = FALSE;
      $options = explode("\n", $field_data["display_options"]);
      $select_options = array();

      foreach ($options as $value) {
        $tmp = explode("|", $value);

        if (count($tmp) == 2) {
          $select_options[trim($tmp[0])] = trim($tmp[1]);
        }

        else {
          $select_options[trim($tmp[0])] = trim($tmp[0]);
        }

      }

      $field['#options'] = isset($select_options) ? $select_options : array();
    }

    elseif ($field_data["display"] == "date") {
      $field['#type'] = 'date';
    }

    else {
      $field['#type'] = 'textfield';
    }

    return $field;
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $mail = $form_state->getValue(['cr_form_wrapper', $this->block_id, 'cr_block_mail_' . $this->block_id]);
    $valid = \Drupal::service('email.validator')->isValid($mail);
    if (!$valid) {
      $form_state->setErrorByName('cr_form_wrapper][' . $this->block_id . '][cr_block_mail_' . $this->block_id, $this->t('Please enter a valid email address.'));
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $db = \Drupal::database();
    $source = \Drupal::config('system.site')->get('name');
    $listid = $db->query('SELECT bf.listid FROM {cleverreach_block_forms} bf WHERE bf.bid = :bid', array(':bid' => $this->block_id))->fetchField();
    $fields = $db->query('SELECT bf.fields FROM {cleverreach_block_forms} bf WHERE bf.bid = :bid', array(':bid' => $this->block_id))->fetchField();
    $un_fields = unserialize($fields);
    $attr = array();

    if (count($un_fields) > 0) {

      foreach ($un_fields as $value) {

        if ($value["active"] == 1) {

          $attr[] = array('key' => $value["name"], 'value' => $values['cr_form_wrapper'][$this->block_id]['cr_block_' . $value["name"] . '_' . $this->block_id]);

        }

      }

    }

    if (count($attr) > 0) {
      $user = array(
        "email" => $values['cr_form_wrapper'][$this->block_id]['cr_block_mail_' . $this->block_id],
        "registered" => time(),
        "activated" => time(),
        "source" => $source,
        "attributes" => $attr,
      );
    }
    else {
      $user = array(
        "email" => $values['cr_form_wrapper'][$this->block_id]['cr_block_mail_' . $this->block_id],
        "registered" => time(),
        "activated" => time(),
        "source" => $source,
      );
    }

    $config = \Drupal::service('config.factory')->get('cleverreach.settings');
    $api = new \SoapClient($config->get('wsdl_url'));
    $result = $api->receiverAdd($config->get('api_key'), $listid, $user);

    if ($result->status == "SUCCESS") {
      drupal_set_message($this->t('Your submission was successfully.'));
    }
    else {
      drupal_set_message($this->t('Error: Your submission failed.'), 'error');
    }
  }
  
}