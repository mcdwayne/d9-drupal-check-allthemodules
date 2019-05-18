<?php

namespace Drupal\intelligent_tools_auto_tag\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Presents the module settings form.
 */
class AutoTagDelForm extends ConfigFormBase {

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
    $config = \Drupal::config('intelligent_tools.settings');
    $content_type_node = $config->get('intelligent_tools_content');
    $content_type_node = strtolower($content_type_node);
    $content_type_node_array = explode(" ", $content_type_node);
    $content_type_field = $config->get('intelligent_tools_field');
    $content_type_field_array = explode(" ", $content_type_field);
    $content_to_be_tagged = $config->get('intelligent_tools_field_to');
    $content_to_be_tagged_array = explode(" ", $content_to_be_tagged);
    $number_of_tags = $config->get('intelligent_tools_tags');
    $number_of_tags_array = explode(" ", $number_of_tags);
    for ($j = 0; $j < sizeof($content_type_node_array); $j++) {
      $some_array[$j] = [$content_type_node_array[$j], $content_type_field_array[$j], $content_to_be_tagged_array[$j], $number_of_tags_array[$j]];
    }
    $length = sizeof($some_array);
    for ($j = 0; $j < $length; $j++) {
      if ($some_array[$j] == $pid_arr) {
        unset($some_array[$j]);
        break;
      }
    }
    $some_other_array = array_values($some_array);
    $texts = [];
    foreach ($some_other_array as $key) {
      $texts[] = implode("###inner###", $key);
    }
    $text_data = implode("###outer###", $texts);
    $form['description'] = [
      '#markup' => '<div>' . t('Are you sure you want to delete this setting?') . '<br>' . t('Content Type: ') . $pid_arr[0] . '<br>' . t('Extracting from: ') . $pid_arr[1] . '<br>' . t('Inserting into: ') . $pid_arr[2] . '<br>' . t('Number of Tags: ') . $pid_arr[3] . '</div>',
    ];

    $form['pid'] = [
      '#type' => 'hidden',
      '#value' => $text_data,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete configuration'),
      '#button_type' => 'primary',
    ];
    $form['#theme'] = 'system_config_form';

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $text_data = $form_state->getValue('pid');
    $outer_data = explode("###outer###", $text_data);
    $data = [];
    foreach ($outer_data as $key) {
      $data[] = explode("###inner###", $key);
    }
    $arr_1 = [];
    $arr_2 = [];
    $arr_3 = [];
    $arr_4 = [];
    foreach ($data as $key => $value) {
      $arr_1[] = $value[0];
      $arr_2[] = $value[1];
      $arr_3[] = $value[2];
      $arr_4[] = $value[3];
    }
    $text_1 = implode(" ", $arr_1);
    $text_2 = implode(" ", $arr_2);
    $text_3 = implode(" ", $arr_3);
    $text_4 = implode(" ", $arr_4);
    $this->config('intelligent_tools.settings')
      ->set('intelligent_tools_content', $text_1)
      ->set('intelligent_tools_field', $text_2)
      ->set('intelligent_tools_field_to', $text_3)
      ->set('intelligent_tools_tags', $text_4)
      ->save();
    $form_state->setRedirect('intelligent_tools_auto_tag.settings');
    parent::submitForm($form, $form_state);
  }

}
