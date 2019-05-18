<?php

namespace Drupal\googlemap_block\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class EditLocation.
 *
 * @package Drupal\googlemap_block\Form
 */
class EditLocation extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'googlemap_block.EditLocation',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edit_google_map_location';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $current_url = Url::fromRoute('<current>');
    $current_path = $current_url->toString();
    $pathArr = explode('/', $current_path);
    $location_id = end($pathArr);
    if ($location_id) {
      $query = db_select('google_map_location_list', 'u');
      $query->fields('u');
      $query->condition('lid', $location_id);
      $results = $query->execute()->fetchAll();
      $form['location_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Name'),
        '#default_value' => $results[0]->location_name,
        '#required' => TRUE,
      ];
      $form['location_address'] = [
        '#type' => 'text_format',
        '#title' => $this->t('Other details'),
        '#default_value' => $results[0]->address,
        '#cols' => 2,
      ];
      $form['location_id'] = [
        '#type' => 'hidden',
        '#value' => $location_id,
      ];
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Edit Location'),
      ];
      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $location_name = $values['location_name'];
    $location_address = !empty($values['location_address']['value']) ? $values['location_address']['value'] : '';
    if ($location_name || $location_address) {
      $latLongAddress = $location_name . ' ' . strip_tags($location_address);
      $latLong = googlemap_block_lat_long($latLongAddress);
      if (!$latLong) {
        $form_state->setErrorByName('location_name', $this->t('Please enter valid address.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $location_name = $values['location_name'];
    $location_address = !empty($values['location_address']['value']) ? $values['location_address']['value'] : '';
    $location_id = $values['location_id'];
    if ($location_name || $location_address) {
      $latLongAddress = $location_name . ' ' . strip_tags($location_address);
      $latLong = googlemap_block_lat_long($latLongAddress);
      $latitude = $latLong['latitude'];
      $longitude = $latLong['longitude'];
    }
    if ($location_name) {
      // Update google location.
      db_update('google_map_location_list')
        ->fields(
            [
              'location_name' => $location_name,
              'address' => strip_tags($location_address) ? $location_address : $location_name,
              'latitude' => $latitude,
              'longitude' => $longitude,
            ]
        )
        ->condition('lid', $location_id)
        ->execute();
    }
    drupal_set_message($this->t('@location location has been Updated', ['@location' => $location_name]));
    $form_state->setRedirectUrl(Url::fromRoute('googlemap_block.map_location'));
  }

}
