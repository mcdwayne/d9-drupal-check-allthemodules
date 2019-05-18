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
use Drupal\Core\Link;
use Drupal\Core\Url;

class GPXTrackEleWPTForm extends FormBase {  //ATTENZIONE non dovrebbe essere un configform

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'gpx_track_elevation_wpt_form';
  }
  
  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor
    //$form = parent::buildForm($form, $form_state);
    // Default settings
    $config = $this->config('gpx_track_elevation.settings');
    
  $header = array(
    t('Type'),
    t('Image url'),
    array('data' => t('Operations'), 'colspan' => 2),
  );
  
  $query = \Drupal::database()->select('gpx_track_elevation', 'w');

  foreach ($query->fields('w')->execute()->fetchAll() as $wpt_type) {
    $rows[] = array(
      $wpt_type->type, // ATTENZIONE RIVEDERE THEMI E ASSICURARSI SIA PLAINTEXT
      UrlHelper::stripDangerousProtocols($wpt_type->url), // ATTENZIONE RIVEDERE THEMI E ASSICURARSI SIA PLAINTEXT
      Link::fromTextAndUrl(t('Edit'),
        Url::fromUri('base:admin/config/services/GPXtrackele/waypoints/edit/' . $wpt_type->wid)),
      Link::fromTextAndUrl(t('Delete'),
        Url::fromUri('base:admin/config/services/GPXtrackele/waypoints/delete/' . $wpt_type->wid)),
    );
  };

  if (!$rows) {
    $rows[] = array(
      array(
        'data' => t('No types defined.'),
        'colspan' => 4,
      ),
    );
  }

  $form['types_table'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
  );
  
  return $form;
  }
  
  /**
   * {@inheritdoc}.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('gpx_track_elevation.settings');
    $config->set('gpx_track_elevation.source_text', $form_state->getValue('source_text'));
    $config->set('gpx_track_elevation.page_title', $form_state->getValue('page_title'));
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