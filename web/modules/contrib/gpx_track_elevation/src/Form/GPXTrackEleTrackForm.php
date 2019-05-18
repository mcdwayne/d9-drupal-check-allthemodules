<?php

/**
 * @file
 * Contains Drupal\gpx_track_elevation\Form\GPXTrackEleTrackForm.
 */

namespace Drupal\gpx_track_elevation\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;

define("MAP_IMAGE_MAX_SIZE", 128);
  
class GPXTrackEleTrackForm extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'gpx_track_elevation_track_settings_form';
  }
  
  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor
    $form = parent::buildForm($form, $form_state);
    // Default settings
    $config = $this->config('gpx_track_elevation.settings');
    
    $form['start_url'] = array(
      '#type' => 'textfield',
      '#title' => t('Image for track start point'),
      '#maxlength' => 255,
      '#default_value' => $config->get('gpx_track_elevation.start_url'),
      '#description' => t("Insert the url of the image to be used to show starting points. Max size: @max_size x @max_size px", array('@max_size' => MAP_IMAGE_MAX_SIZE)),
    );
    $form['end_url'] = array(
      '#type' => 'textfield',
      '#title' => t('Image for track end point'),
      '#maxlength' => 255,
      '#default_value' => $config->get('gpx_track_elevation.end_url'),
      '#description' => t("Insert the url of the image to be used to show ending points. Max size: @max_size x @max_size px", array('@max_size' => MAP_IMAGE_MAX_SIZE)),
    );

    $form['last_url'] = array(
      '#type' => 'textfield',
      '#title' => t('Image for track end point of last track'),
      '#maxlength' => 255,
      '#default_value' => $config->get('gpx_track_elevation.last_url'),
      '#description' => t("Insert the url of the image to be used to show ending points of the last Track. Max size: @max_size x @max_size px", array('@max_size' => MAP_IMAGE_MAX_SIZE)),
    );

    return $form;
  }
  
  /**
   * {@inheritdoc}.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $start_url = Html::escape(UrlHelper::stripDangerousProtocols($form_state->getValue('start_url')));
    if ((UrlHelper::isValid($start_url, TRUE) <> TRUE) && !(is_null($start_url) || $start_url == '')) {
      $form_state->setErrorByName('start_url', t('%start_url is an invalid url address.', array('%start_url' => $start_url)));
    }

    $end_url = Html::escape(UrlHelper::stripDangerousProtocols($form_state->getValue('end_url')));
    if ((UrlHelper::isValid($end_url, TRUE) <> TRUE) && !(is_null($end_url) || $end_url == '')) {
      $form_state->setErrorByName('end_url', t('%end_url is an invalid url address.', array('%end_url' => $end_url)));
    }

    $last_url = Html::escape(UrlHelper::stripDangerousProtocols($form_state->getValue('last_url')));
    if ((UrlHelper::isValid($last_url, TRUE) <> TRUE) && !(is_null($last_url) || $last_url == '')) {
      $form_state->setErrorByName('last_url', t('%last_url is an invalid url address.', array('%last_url' => $last_url)));
    }

    if (!(is_null($start_url) || $start_url == '')) {
      if ($image_size = @getimagesize($start_url)) {
        if ($image_size[0] > MAP_IMAGE_MAX_SIZE || $image_size[1] > MAP_IMAGE_MAX_SIZE) {
          $form_state->setErrorByName('start_url', t('Image is to big (max %max_size * %max_size pixel)', array('%max_size' => MAP_IMAGE_MAX_SIZE)));
        }
      }
      else {
        $form_state->setErrorByName('start_url', t('Cannot load image at %start_url.', array('%start_url' => $start_url)));
      }
    }

    if (!(is_null($end_url) || $end_url == '')) {
      if ($image_size = @getimagesize($end_url)) {
        if ($image_size[0] > MAP_IMAGE_MAX_SIZE || $image_size[1] > MAP_IMAGE_MAX_SIZE) {
          $form_state->setErrorByName('end_url', t('Image is to big (max %max_size * %max_size pixel)', array('%max_size' => MAP_IMAGE_MAX_SIZE)));
        }
      }
      else {
        $form_state->setErrorByName('end_url', t('Cannot load image at %end_url.', array('%end_url' => $end_url)));
      }
    }
    if (!(is_null($last_url) || $last_url == '')) {
      if ($image_size = @getimagesize($last_url)) {
        if ($image_size[0] > MAP_IMAGE_MAX_SIZE || $image_size[1] > MAP_IMAGE_MAX_SIZE) {
          $form_state->setErrorByName('last_url', t('Image is to big (max %max_size * %max_size pixel)', array('%max_size' => MAP_IMAGE_MAX_SIZE)));
        }
      }
      else {
        $form_state->setErrorByName('last_url', t('Cannot load image at %last_url.', array('%last_url' => $last_url)));
      }
    }

  }

  /**
   * {@inheritdoc}.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('gpx_track_elevation.settings');
  
    $config->set('gpx_track_elevation.start_url', UrlHelper::stripDangerousProtocols($form_state->getValue('start_url')));
    $config->set('gpx_track_elevation.end_url', UrlHelper::stripDangerousProtocols($form_state->getValue('end_url')));
    $config->set('gpx_track_elevation.last_url', UrlHelper::stripDangerousProtocols($form_state->getValue('last_url')));

    $config->save();
    return parent::submitForm($form, $form_state);
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