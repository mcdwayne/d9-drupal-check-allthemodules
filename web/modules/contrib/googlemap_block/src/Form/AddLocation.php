<?php

namespace Drupal\googlemap_block\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Class AddLocation.
 *
 * @package Drupal\googlemap_block\Form
 */
class AddLocation extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'googlemap_block.AddLocation',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_map_location_add';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $link = Link::createFromRoute($this->t('Address list'), 'googlemap_block.map_location')->toString();
    $form['#prefix'] = $link;
    $form['location_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#required' => TRUE,
    ];
    $form['location_address'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Other details'),
      '#cols' => 2,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Location'),
    ];
    return $form;
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
    if ($location_name || $location_address) {
      $latLongAddress = $location_name . ' ' . strip_tags($location_address);
      $latLong = googlemap_block_lat_long($latLongAddress);
      $latitude = $latLong['latitude'];
      $longitude = $latLong['longitude'];
    }
    if ($location_name) {
      // Add google location.
      db_insert('google_map_location_list')
        ->fields(
            [
              'lid' => NULL,
              'location_name' => $location_name,
              'address' => strip_tags($location_address) ? $location_address : $location_name,
              'latitude' => $latitude,
              'longitude' => $longitude,
            ])
        ->execute();
    }
    drupal_set_message($this->t('@location location has been Updated', ['@location' => $location_name]));
  }

}
