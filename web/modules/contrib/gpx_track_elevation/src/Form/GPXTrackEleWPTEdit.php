<?php

/**
 * @file
 * Contains Drupal\gpx_track_elevation\Form\GPXTrackEleForm.
 */

namespace Drupal\gpx_track_elevation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;

define("MAP_IMAGE_MAX_SIZE", 128);

class GPXTrackEleWPTEdit extends FormBase { //ATTENZIONE non dovrebbe essere un configform

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'gpx_track_elevation_wpt_edit_form';
  }
  
  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $wid = array()) {
    // Form constructor
    
    $wid += array(
      'wid' => NULL,
      'type' => '',
      'url' => '',
      'weight' => 0,
    );

    $wpt_type = $wid;

    //$form = parent::buildForm($form, $form_state);

    $form['type'] = array(
      '#type' => 'textfield',
      '#title' => t('Waypoint type name'),
      '#maxlength' => 255,
      '#default_value' => $wpt_type['type'],
      '#description' => t("Insert the type name. It is the string expected in the <type> tag of the gpx waypoint."),
      '#required' => TRUE,
    );
    $form['url'] = array(
      '#type' => 'textfield',
      '#title' => t('Image url'),
      '#maxlength' => 255,
      '#default_value' => $wpt_type['url'],
      '#description' => t("Insert the url of the image to be used to show this waypoint type on the map. Only square images are allowed. Max size: @max_size x @max_size px", array('@max_size' => MAP_IMAGE_MAX_SIZE)),
      '#required' => TRUE,
    );
    $form['weight'] = array(
      '#type' => 'weight',
      '#title' => t('Weight'),
      '#default_value' => $wpt_type['weight'],
      '#description' => t('When listing categories, those with lighter (smaller) weights get listed before categories with heavier (larger) weights. Categories with equal weights are sorted alphabetically.'),
    );
    $form['wid'] = array(
      '#type' => 'value',
      '#value' => $wpt_type['wid'],
    );
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );
    
    return $form;
  }
  
  /**
   * {@inheritdoc}.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // When creating a new wpt type, or renaming the wpèt type on an existing
    // one, make sure that the given wpt type is unique.
    $wpt_type = $form_state->getValue('type');
    $query = \Drupal::database()->select('gpx_track_elevation', 'w')->condition('w.type', $wpt_type, '=');
    if (!empty($form_state->getValue('wid'))) {
      $query->condition('w.wid', $form_state->getValue('wid'), '<>');
    }
    if ($query->countQuery()->execute()->fetchField()) {
      $form_state->setErrorByName('type', t('A waypoint type category %wpt_type already exists.', array('%wpt_type' => $wpt_type)));
    }

    $wpt_url = Html::escape(UrlHelper::stripDangerousProtocols($form_state->getValue('url')));
    $isValidUrl = UrlHelper::isValid($wpt_url,TRUE);
    
    if ($isValidUrl <> TRUE) {
      $form_state->setErrorByName('url', t('%wpt_url is an invalid url address.', array('%wpt_url' => $wpt_url)));
    }
    
    if ($image_size = @getimagesize($wpt_url)) {
      if ($image_size[0] > MAP_IMAGE_MAX_SIZE || $image_size[1] > MAP_IMAGE_MAX_SIZE) {
        $form_state->setErrorByName('url', t('Image is to big (max %max_size * %max_size pixel)', array('%max_size' => MAP_IMAGE_MAX_SIZE)));
      }
      if ($image_size[0] <> $image_size[1]) {
        $form_state->setErrorByName('url', t('Only square image are allowed'));
      }
    }
    else {
      $form_state->setErrorByName('url', t('Cannot load image at %wpt_url.', array('%wpt_url' => $wpt_url)));
    }
  }

  /**
   * {@inheritdoc}.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    //dpm($form_state->getValues());
    if (empty($form_state->getValue('wid'))) {
      $query = \Drupal::database()->insert('gpx_track_elevation');
    }
    else {
      $query = \Drupal::database()->update('gpx_track_elevation');
      $query->condition('wid',$form_state->getValue('wid'));
    }

    $query->fields(
      array (
        'weight' => $form_state->getValue('weight'),
        'type' => $form_state->getValue('type'),
        'url' => $form_state->getValue('url'),
      )
    );

    if ($query->execute()) {
      drupal_set_message(t('Waypoint type %wpt_type has been saved.', array('%wpt_type' => $form_state->getValue('type'))));
    }
    else {
      drupal_set_message(t('Waypoint type %wpt_type has not been correctly saved.', array('%wpt_type' => $form_state->getValue('type'))));
    }
    $form_state->setRedirect('gpx_track_elevation.wpt_form');
    
    //return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}.
   */
  protected function getEditableConfigNames() {
    return [
      'gpx_track_elevation.settings',
    ];
  }

}