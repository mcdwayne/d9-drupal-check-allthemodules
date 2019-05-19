<?php

/**
 * @file
 * Contains \Drupal\demo\Form\DemoForm.
 */

namespace Drupal\youtubeapi\Form;


use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\youtubeapi\YoutubeAPI\API;
use Drupal\youtubeapi\YoutubeAPI\YoutubeSearch;

class Test extends FormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'youtubeapi_test';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $options = [];
    $method_list = self::getYamlMethodList();
    if ($method_list) {
      foreach ($method_list as $method) {
        $options[$method] = $method;
      }
    }

    $form['yapi_methods'] = [
      '#type' => 'select',
      '#title' => $this->t('Method'),
      '#default_value' => "",
      '#options' => $options,
      '#ajax' => [
        'callback' => '::methodChangeAjax',
        'wrapper' => 'edit-fieldsset',
        'method' => 'replace',
        'effect' => 'fade',
        'event' => 'change',
      ],
    ];


    //Ajax changable fields container
    $form['fieldsset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Fields'),
      '#default_value' => "",
      '#prefix' => '<div id="edit-fieldsset">',
      '#suffix' => '</div>',
    ];

    self::createFormFor($form, $form_state, $method[0]);

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => "Submit",
    ];

    return $form;
  }

  function methodChangeAjax($form, FormStateInterface $form_state) {
    $method = $form_state->getValue('yapi_methods');

    self::createFormFor($form, $form_state, $method);

    return $form['fieldsset'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $name = $form_state->getValue('name');
    if (!$name || strlen($name) < 5) {
      $form_state->setErrorByName('name', "Name too short, Please enter your full name");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message("Running ...");

    //$api = new Yoy  YoutubeSearch();
    //$api->addQuery(Search::q, 'drupal');
    //$api->addQuerys([Search::part => 'snippet', Search::type => 'video']);
    //$result = $api->execute();

    return TRUE;
  }

  /**
   * Build form
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $method
   */
  public static function createFormFor(array &$form, FormStateInterface $form_state, $method) {

    $data = self::getYamlMethodData($method);

    if ($data) {
      $form['fieldsset']['#access'] = TRUE;
      $form['fieldsset']['#title'] = $method;
      $datar = $data['request'];

      $form['fieldsset']['metadata'] = [];
      $form['fieldsset']['metadata']['ytapimethod'] = [
        '#type' => 'hidden',
        '#value' => $method,
      ];

      $form['fieldsset']['required'] = [
        '#type' => 'details',
        '#title' => 'Required',
        '#open' => TRUE,
      ];
      $form['fieldsset']['optional'] = [
        '#type' => 'details',
        '#title' => 'Optional',
        '#open' => FALSE,
      ];

      foreach ($datar as $field_name => $field_data) {
        $field_name_hr = $field_name;
        $field_safe = self::getFieldNameSafe($field_name);
        $apitype = empty($field_data['apitype']) ? 'string' : $field_data['apitype'];
        $options = empty($field_data['options']) ? FALSE : $field_data['options'];
        $default = empty($field_data['default']) ? FALSE : $field_data['default'];
        $multiple = empty($field_data['multiple']) ? FALSE : TRUE;
        $required = empty($field_data['required']) ? FALSE : TRUE;

        // Is a required field.
        $fieldsset_type = $required ? 'required' : 'optional';

        //Field changes.
        $form_type = "textfield";
        if ($options) {
          $form_type = "select";
        }
        elseif ($apitype == 'boolean') {
          $form_type = "checkbox";
        }


        //Add to form
        $form['fieldsset'][$fieldsset_type][$field_safe] = [
          '#type' => $form_type,
          '#title' => $field_name_hr,
          '#required' => $required,
          '#options' => $options,
          '#default_value' => $default,
        ];
        if ($multiple) {
          $form['fieldsset'][$fieldsset_type][$field_safe]['#description'] = "Multiple allowed";
        }
      }
    }
    else {
      $form['fieldsset']['#title'] = "Please select a type";
    }
  }

  /**
   * Build form
   * @param $field_name
   * @return string
   */
  public static function getFieldNameSafe($field_name) {
    $string = preg_replace("/[^A-Za-z0-9 ]/", '', $field_name);
    $string = "datafield" . $string;
    return $string;
  }

  /**
   * Read Yaml method data file.
   * @param $method
   * @return mixed|null
   */
  public static function getYamlMethodData($method) {
    $file_path = DRUPAL_ROOT . '/' . drupal_get_path('module', 'youtubeapi') . '/config/api/' . $method . '.yml';
    if (file_exists($file_path)) {
      return Yaml::decode(file_get_contents($file_path));
    }
    return NULL;
  }

  /**
   * Read Yaml method List.
   * @return array|null
   */
  public static function getYamlMethodList() {
    $file_path = DRUPAL_ROOT . '/' . drupal_get_path('module', 'youtubeapi') . '/config/methods.yml';
    if (file_exists($file_path)) {
      $method_list = Yaml::decode(file_get_contents($file_path));
      if (isset($method_list['methods'])) {
        return $method_list['methods'];
      }
    }
    return NULL;
  }
}
