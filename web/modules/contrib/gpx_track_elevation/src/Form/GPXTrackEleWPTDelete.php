<?php

/**
 * @file
 * Contains Drupal\gpx_track_elevation\Form\GPXTrackEleForm.
 */

namespace Drupal\gpx_track_elevation\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;


class GPXTrackEleWPTDelete extends ConfirmFormBase { 

  public function getQuestion() {
    return $this->t('Are you really sure?');
  }  
  
  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'gpx_track_elevation_wpt_delete_form';
  }
  
  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $wid = array()) {
    // Form constructor
    $form['waypoint_types'] = array(
      '#type' => 'value',
      '#value' => $wid,
    );

    $form = parent::buildForm($form, $form_state);
    return $form;
  }
  
  /**
   * {@inheritdoc}.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) { // ATTENZIONE RIVEDERE SE I DATI LI DEVO PASSARE CON UN INJECTION

    //dpm($form['waypoint_types']);
    $form_state->setRedirect('gpx_track_elevation.wpt_form');

    $form['waypoint_types']['#value'];

    $result = \Drupal::database()->delete('gpx_track_elevation')->condition('wid', $form['waypoint_types']['#value']['wid'], '=')->execute();
    
    drupal_set_message(t('Waypoint type %wpt_type has been deleted.', array('%wpt_type' => $form['waypoint_types']['#value']['type'].' ('.$result.')')));
    \Drupal::logger('gpx_track_elevation')->notice(t('Waypoint type %wpt_type has been deleted.', array('%wpt_type' => $form['waypoint_types']['#value']['type'].' ('.$result.')')));
  }

  public function getCancelUrl () {
    return Url::fromRoute('gpx_track_elevation.wpt_form');
  }

  public function getConfirmText() {
    return $this->t('Delete');
  }

  public function getCancelText() {
    return $this->t('Cancel');
  }
  
  public function getDescription() { // ATTENZIONE RIVEDERE SE I DATI LI DEVO PASSARE CON UN INJECTION SENZA LA QUALE NON ACCEDIAMO AI DATI PER DIRE CHE TYPE ELIMINO
    return t('Are you sure you want to delete selected waypoint type?').'<br>'.parent::getDescription();
  }

}