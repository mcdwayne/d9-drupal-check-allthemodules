<?php

/**
 * @file
 * Contains \Drupal\bmi\Form\BmiForm.
 */

namespace Drupal\bmi\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BmiForm.
 *
 * @package Drupal\bmi\Form
 */
class BmiForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bmi_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['body_weight'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Weight'),
      '#maxlength' => 64,
      '#size' => 10,
    );
    $form['weight_units'] = array(
      '#type' => 'select',
      '#title' => $this->t('Units'),
      '#options' => array('kgs' => $this->t('kgs'), 'lbs' => $this->t('lbs')),
    );
    $form['body_height'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#maxlength' => 64,
      '#size' => 10,
    );
    $form['height_units'] = array(
      '#type' => 'select',
      '#title' => $this->t('Units'),
      '#options' => array('cms' => $this->t('cms'), 'mts' => $this->t('mts')),
    );
    $form['bmi_result'] = array(
      '#prefix' => '<div id="box">',
      '#suffix' => '</div>',
      '#markup' => '',
    );
    $form['my_submit'] = array(
      '#type' => 'button',
      '#value' => t('Calculate'),
      '#ajax' => array(
          'callback' => '::bmi_calculate',
          'event' => 'click',
          'wrapper' => 'box',
          'progress' => array(
            'type' => 'throbber'
          ),

        ),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  function bmi_calculate(array &$form, FormStateInterface $form_state) {
    $body_weight = $form_state->getValue('body_weight');
    $body_height = $form_state->getValue('body_height');
    $weight_unit = $form_state->getValue('weight_units');
    $height_unit = $form_state->getValue('height_units');

    if ((is_numeric($body_weight)) && (is_numeric($body_height))) {
      $body_weight = $this->convert_weight_kgs($body_weight, $weight_unit);
      $body_height = $this->convert_height_mts($body_height, $height_unit);
      $bmi = 1.3*$body_weight/pow($body_height,2.5);
      $bmi = round($bmi, 2);
      $bmi_std = $body_weight/($body_height*$body_height);
      $bmi_std = round($bmi_std, 2);
      $bmi_text = $this->get_bmi_text($bmi);
      $output = t("Your BMI value according to the Quetelet formula is");
      $output .= " <b>". $bmi_std ."</b><br>";
      $output .= t("Your adjusted BMI value according to Nick Trefethen of
      <a href='http://www.ox.ac.uk/media/science_blog/130116.html' target='_blank'>Oxford University's Mathematical Institute</a> is");
      $output .= " <b>". $bmi ."</b><br>". $bmi_text;
    }
    else {
      $output = "Please enter numeric values for weight and height fields";
    }
    $element = $form['bmi_result'];
    $element['#markup'] = $output;
    return $element;
  }
  function convert_weight_kgs($body_weight = NULL, $weight_unit = NULL) {
    if ($weight_unit == 'lbs') {
    // 1pound = 0.4359237
      return $body_weight * 0.4536;
    }
    // return the weight as is bcoz it is in kg only
      return $body_weight;
  }

  function convert_height_mts($body_height = NULL, $height_unit = NULL) {
    switch ($height_unit) {
      case 'mts':
        return $body_height;
        break;
      case 'cms':
      // 1 cms = 0.01 m.
        return $body_height * 0.01;
    }
  }

  function get_bmi_text($bmi = NULL) {
    $column = '';
    $config = $this->config('bmi.bodymassindexsettings');
    if ($bmi <= 18.5)
      $column = $config->get('underweight_text');
    elseif ($bmi > 18.5 && $bmi <= 24.9)
      $column = $config->get('normalweight_text');
    elseif ($bmi >24.9 && $bmi <= 29.9)
      $column = $config->get('overweight_text');
    elseif ($bmi > 29.9)
      $column = $config->get('obesity_text');
    return $column;
  }

}
