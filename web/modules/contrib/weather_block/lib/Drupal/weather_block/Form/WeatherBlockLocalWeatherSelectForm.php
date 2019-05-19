<?php

namespace Drupal\weather_block\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\weather_block\Plugin\Block\weather_blockBlock;
use Drupal\Core\Annotation\Translation;

class WeatherBlockLocalWeatherSelectForm implements FormInterface {

  public function __construct($city) {

    $this->default_city = $city;
  }

  public function getFormID() {
    return 'weather_block_local_form_' . $this->default_city;
  }

  public function buildForm(array $form, array &$form_state) {

    $options = array();

    $query = \Drupal::entityQuery('taxonomy_term');

    $result = $query->condition('field_local_city', 1)
                    ->execute();

    foreach ($result as $tid) {

      $query = db_query("select * from taxonomy_term_data where tid = '" . $tid . "'");

      $result = $query->fetchAll();

      $options[$result[0]->tid] = $result[0]->name;
    }

    $form['#title'] = 'test';

    $form['weather_city'] = array(
      '#type' => 'select',
      '#title' => t('Select the city'),
      '#options' => $options,
      '#default_value' => $this->default_city,
    );

    $form['block_submit'] = array(
      '#type' => 'submit',
      '#value' => t('Change'),
    );

    return $form;
  }

  public function validateForm(array &$form, array &$form_state) {

  }

  public function submitForm(array &$form, array &$form_state) {

    $_SESSION['weather_city_local'] = $form_state['values']['weather_city'];
  }
}

