<?php

namespace Drupal\intelligent_tools_duplicate_rate\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Presents the module settings form.
 */
class DuplicateRateEditForm extends ConfigFormBase {

  public $pid;

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
  public function buildForm(array $form, FormStateInterface $form_state, $pid = NULL) {
    $pid_arr = explode("###", $pid);
    $config = $this->config('intelligent_tools_duplicate_rate.settings');
    $content_entity_types = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
    $contentTypesList = [];
    foreach ($content_entity_types as $contentType) {
      $contentTypesList[$contentType->id()] = $contentType->label();
    }
    $form['intelligent_tools_duplicate_rate_content'] = [
      '#type' => 'select',
      '#options' => $contentTypesList,
      '#title' => $this->t('Content Type'),
      '#default_value' => $pid_arr[0],
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
      '#default_value' => $pid_arr[1],
      '#description' => 'Field used to extract Duplicate Rate from',
      '#required' => TRUE,
    ];

    $form['pid'] = [
      '#type' => 'hidden',
      '#value' => $pid,
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
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $pid = $form_state->getValue('pid');
    $pid_arr = explode("###", $pid);
    $config = \Drupal::config('intelligent_tools.settings');
    $content_type_node = $config->get('intelligent_tools_duplicate_rate_content');
    $content_type_node = strtolower($content_type_node);
    $content_type_node_array = explode(" ", $content_type_node);
    $content_type_field = $config->get('intelligent_tools_duplicate_rate_field');
    $content_type_field_array = explode(" ", $content_type_field);
    for ($j = 0; $j < sizeof($content_type_node_array); $j++) {
      $some_array[$j] = [$content_type_node_array[$j], $content_type_field_array[$j]];
    }
    $temp_arr = [$form_state->getValue('intelligent_tools_duplicate_rate_content'), $form_state->getValue('intelligent_tools_duplicate_rate_field')];
    $length = sizeof($some_array);
    for ($j = 0; $j < $length; $j++) {
      if ($some_array[$j] == $pid_arr) {
        $some_array[$j] = $temp_arr;
        break;
      }
    }
    $arr_1 = [];
    $arr_2 = [];
    foreach ($some_array as $key => $value) {
      $arr_1[] = $value[0];
      $arr_2[] = $value[1];
    }
    $text_1 = implode(" ", $arr_1);
    $text_2 = implode(" ", $arr_2);
    $this->config('intelligent_tools.settings')
      ->set('intelligent_tools_duplicate_rate_content', $text_1)
      ->set('intelligent_tools_duplicate_rate_field', $text_2)
      ->save();
    $form_state->setRedirect('intelligent_tools_duplicate_rate.settings');
    parent::submitForm($form, $form_state);
  }

}
