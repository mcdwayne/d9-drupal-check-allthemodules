<?php

namespace Drupal\intelligent_tools_duplicate_rate\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Presents the module settings form.
 */
class DuplicateRateAddForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'intelligent_tools_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['intelligent_tools.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('intelligent_tools.settings');
    $content_type_node = $config->get('intelligent_tools_duplicate_rate_content');
    $content_type_node = strtolower($content_type_node);
    $content_type_node_array = explode(" ", $content_type_node);
    $content_entity_types = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
    $contentTypesList = [];
    foreach ($content_entity_types as $contentType) {
      $contentTypesList[$contentType->id()] = $contentType->label();
    }
    $ip_display = $config->get('intelligent_tools_duplicate_rate_ip');
    if ($ip_display == '') {
      $form['intelligent_tools_duplicate_rate_ip'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Web Address'),
        '#description' => t('Your POST fetch Address'),
        '#required' => TRUE,
      ];
    }
    $form['intelligent_tools_duplicate_rate_content'] = [
      '#type' => 'select',
      '#options' => $contentTypesList,
      '#title' => $this->t('Content Type'),
      '#default_value' => 'article',
      '#description' => 'Use a content type that has Duplicate content field',
      '#required' => TRUE,
    ];
    $entityTypeList = [];
    foreach ($contentTypesList as $cont_type => $cont_val) {
      foreach (\Drupal::entityManager()->getFieldDefinitions('node', strtolower($cont_type)) as $field_name => $field_definition) {
        if (!empty($field_definition->getTargetBundle())) {
          if ($field_name == 'field_image' || $field_name == 'promote') {
            continue;
          }
          $entityTypeList[$field_name] = $field_name;
        }
      }
    }
    $form['intelligent_tools_duplicate_rate_field'] = [
      '#type' => 'select',
      '#options' => $entityTypeList,
      '#title' => $this->t('Extract from'),
      '#default_value' => 'body',
      '#description' => 'Field used to extract Duplicate Rate from',
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);

  }

  /**
   *
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::config('intelligent_tools.settings');
    parent::validateForm($form, $form_state);
    $values = $form_state->getValues();
    $nodeType = strtolower($values['intelligent_tools_duplicate_rate_content']);
    foreach (\Drupal::entityManager()->getFieldDefinitions('node', $nodeType) as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle())) {
        $bundleFields[$field_name] = $field_definition->getLabel();
      }
    }
    $allkeys = array_keys($bundleFields);
    if (!in_array($values['intelligent_tools_duplicate_rate_field'], $allkeys)) {
      $form_state->setErrorByName('intelligent_tools_duplicate_rate_field', $this->t('Field is not valid'));
    }
    $ip_display = $config->get('intelligent_tools_duplicate_rate_ip');
    if ($ip_display == '') {
      $url = $form_state->getValue('intelligent_tools_duplicate_rate_ip');
      $validate_response = (bool) preg_match("\n      /^                                                      # Start at the beginning of the text\n      (?:ftp|https?|feed):\\/\\/                                # Look for ftp, http, https or feed schemes\n      (?:                                                     # Userinfo (optional) which is typically\n        (?:(?:[\\w\\.\\-\\+!\$&'\\(\\)*\\+,;=]|%[0-9a-f]{2})+:)*      # a username or a username and password\n        (?:[\\w\\.\\-\\+%!\$&'\\(\\)*\\+,;=]|%[0-9a-f]{2})+@          # combination\n      )?\n      (?:\n        (?:[a-z0-9\\-\\.]|%[0-9a-f]{2})+                        # A domain name or a IPv4 address\n        |(?:\\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\\])         # or a well formed IPv6 address\n      )\n      (?::[0-9]+)?                                            # Server port number (optional)\n      (?:[\\/|\\?]\n        (?:[\\w#!:\\.\\?\\+=&@\$'~*,;\\/\\(\\)\\[\\]\\-]|%[0-9a-f]{2})   # The path and query (optional)\n      *)?\n    \$/xi", $url);
      if ($validate_response != TRUE) {
        $form_state->setErrorByName('intelligent_tools_duplicate_rate_ip', $this->t('URL is not valid'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $new_flag = TRUE;
    $config = \Drupal::config('intelligent_tools.settings');
    $content_type_node = $config->get('intelligent_tools_duplicate_rate_content');
    $content_type_node = strtolower($content_type_node);
    $content_type_node_array = explode(" ", $content_type_node);
    $content_type_field = $config->get('intelligent_tools_duplicate_rate_field');
    $content_type_field_array = explode(" ", $content_type_field);
    $temp_arr = [$form_state->getValue('intelligent_tools_duplicate_rate_content'), $form_state->getValue('intelligent_tools_duplicate_rate_field')];
    if ($content_type_node_array[0] != '') {
      for ($j = 0; $j < sizeof($content_type_node_array); $j++) {
        $some_array[$j] = [$content_type_node_array[$j], $content_type_field_array[$j]];
      }
      $length = sizeof($some_array);
      for ($j = 0; $j < $length; $j++) {
        if ($some_array[$j] == $temp_arr) {
          $new_flag = FALSE;
          break;
        }
      }
    }
    if ($new_flag == TRUE) {
      $some_array[] = $temp_arr;
    }
    $arr_1 = [];
    $arr_2 = [];
    foreach ($some_array as $key => $value) {
      $arr_1[] = $value[0];
      $arr_2[] = $value[1];
    }
    $text_1 = implode(" ", $arr_1);
    $text_2 = implode(" ", $arr_2);
    $ip_display = $config->get('intelligent_tools_duplicate_rate_ip');
    if ($ip_display == '') {
      $this->config('intelligent_tools.settings')
        ->set('intelligent_tools_duplicate_rate_content', $text_1)
        ->set('intelligent_tools_duplicate_rate_field', $text_2)
        ->set('intelligent_tools_duplicate_rate_ip', $form_state->getValue('intelligent_tools_duplicate_rate_ip'))
        ->save();
    }
    else {
      $this->config('intelligent_tools.settings')
        ->set('intelligent_tools_duplicate_rate_content', $text_1)
        ->set('intelligent_tools_duplicate_rate_field', $text_2)
        ->save();
    }
    $form_state->setRedirect('intelligent_tools_duplicate_rate.settings');
    parent::submitForm($form, $form_state);
  }

}
