<?php

/**
 * @file  
 * Contains \Drupal\surveygizmodrupal\Form\SurveygizmoForm.  
 */

namespace Drupal\surveygizmodrupal\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class SurveygizmoForm extends FormBase {

  /**
   * {@inheritdoc}  
   */
  public function getFormId() {
    return 'surveygizmo_survey_form';
  }

  /**
   * {@inheritdoc}  
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {


    $config = \Drupal::config('surveygizmodrupal.adminsettings');
    $SG_API_KEY = $config->get('SG_API_KEY');
    $SG_API_SECRET = $config->get('SG_API_SECRET');

    try {
      \SurveyGizmo\SurveyGizmoAPI::auth($SG_API_KEY, $SG_API_SECRET);
    } 
    catch (\SurveyGizmo\Helpers\SurveyGizmoException $e) {
      die("Error Authenticating");
    }

    \SurveyGizmo\ApiRequest::setRepeatRateLimitedRequest(10);

    if ($id) {
      $survey_id = $id;
    } 
    else {
      die('wrong request');
    }

    $data = '';
    $survey = \SurveyGizmo\Resources\Survey::get($survey_id);
    $id = $survey->id;
    $title = $survey->title;
    $internal_title = $survey->internal_title;
    $links = $survey->links->campaign;
    $created_on = strtotime($survey->created_on);

    $form['#title'] = $title;

    $form['servery_created'] = [
      '#type' => 'item',
      '#title' => t('Created On: '),
      '#markup' => date('d/m/Y H:i:s', $created_on),
    ];

    $form['survery_id'] = [
      '#type' => 'hidden',
      '#value' => $id,
    ];

    $form['survery_url'] = [
      '#type' => 'hidden',
      '#value' => $links,
    ];

    $i = 1;


    foreach ($survey->pages[0]->questions as $key => $value) {
      $question_id = $value->id;
      $question_title = $value->title->English;
      $question_type = $value->type;
      $is_required = FALSE;

      if ($value->properties->required) {
        $is_required = TRUE;
      }

      $options = [];
      $li_zebra = '';

      if ($i % 2 == 0) {
        $li_zebra = ' even';
      }
      else {
        $li_zebra = ' odd';
      }

      $field_type = [
        'TEXTAREA' => 'textarea',
        'ESSAY' => 'textarea',
        'TEXTBOX' => 'textfield',
        'EMAIL' => 'email',
        'CHECKBOX' => 'checkboxes',
        'RADIO' => 'radios',
        'SELECT' => 'select',
        'MENU' => 'select'
      ];


      if ($field_type[$question_type] == 'checkboxes' || $field_type[$question_type] == 'radios') {

        foreach ($value->options as $key_in => $value_in) {
          $options[$value_in->id] = $value_in->value;
        }

        $form['sgE-' . $id . '-1-' . $question_id] = [
          '#type' => $field_type[$question_type],
          '#title' => t('<span class="qtn-number">Q' . $i . '.</span><span class="qtn-text"> ' . $question_title . '</span>'),
          '#required' => $is_required,
          '#options' => $options,
          '#prefix' => '<li class="qution_item' . $li_zebra . '">',
          '#suffix' => '</li>'
        ];
        $i++;
      } 
      else if ($field_type[$question_type] == 'select') {

        foreach ($value->options as $key_in => $value_in) {
          $options[$value_in->id] = $value_in->value;
        }

        $form['sgE-' . $id . '-1-' . $question_id] = [
          '#type' => $field_type[$question_type],
          '#title' => t('<span class="qtn-number">Q' . $i . '.</span><span class="qtn-text"> ' . $question_title . '</span>'),
          '#required' => $is_required,
          '#options' => $options,
          '#prefix' => '<li class="qution_item' . $li_zebra . '">',
          '#suffix' => '</li>'
        ];
        $i++;
      }
      else if ($field_type[$question_type] == 'textfield' && $value->properties->map_key == 'date') {
        $form['sgE-' . $id . '-1-' . $question_id] = [
          '#type' => 'date',
          '#title' => t('<span class="qtn-number">Q' . $i . '.</span><span class="qtn-text"> ' . $question_title . '</span>'),
          '#required' => $is_required,
          '#prefix' => '<li class="qution_item' . $li_zebra . '">',
          '#suffix' => '</li>'
        ];
        $i++;
      } 
      else {
        $form['sgE-' . $id . '-1-' . $question_id] = [
          '#type' => $field_type[$question_type],
          '#title' => t('<span class="qtn-number">Q' . $i . '.</span><span class="qtn-text"> ' . $question_title . '</span>'),
          '#required' => $is_required,
          '#prefix' => '<li class="qution_item' . $li_zebra . '">',
          '#suffix' => '</li>'
        ];
        $i++;
      }
    }
    
    $form['#attached']['library'][] = 'surveygizmo/surveygizmo-js';
    $form['#attached']['library'][] = 'surveygizmo/surveygizmo-css';


    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $survery_id = $form_state->getValue('survery_id');

    $response_key_array = [];
    $survey_gizmo_response = [];

    $total_form_keys = array_keys($form);

    foreach ($total_form_keys as $value) {
      if (substr($value, 0, 3) == 'sgE') {
        $response_key_array[] = $value;
      }
    }

    foreach ($response_key_array as $value) {
      $value_array = explode('-', $value);
      $gizmo_key = $value_array[3];
      $field_value = $form_state->getValue($value);
      if (is_array($field_value)) {
        $multiple_field = [];
        foreach ($field_value as $value) {
          if ($value) {
            $multiple_field[$value] = ['answer' => $value];
          }
        }
        $survey_gizmo_response[$gizmo_key] = ['options' => $multiple_field];
      }
      else {
        $survey_gizmo_response[$gizmo_key] = [
          'options' => [
            $field_value => [
              'answer' => $field_value
            ],
          ]
        ];
      }
    }

    $response = new \SurveyGizmo\Resources\Survey\Response();
    $response->survey_id = $survery_id;
    $response->status = 'Complete';
    $response->survey_data = $survey_gizmo_response;
    $response->save();
  }

}
