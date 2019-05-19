<?php
namespace Drupal\youtubechannel;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * provides Configure settings.
 */

class YoutubechannelSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'youtubechannel_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'youtubechannel.settings'
    ];
  }

 /**
   * {@inheritdoc}
   */
  
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $config = $this->config('youtubechannel.settings');
	  
	
    if(empty($config->get('youtubechannel_api_key'))) {
      $youtubechannel_api_key = "";
    }
    else {
      $youtubechannel_api_key = $config->get('youtubechannel_api_key');
    }
    
    if(empty($config->get('youtubechannel_id'))) {
      $youtubechannel_id = '';
    }
    else {
      $youtubechannel_id = $config->get('youtubechannel_id');
    }
    
    if(empty($config->get('youtubechannel_video_limit'))) {
      $youtubechannel_video_limit = 5;
    }
    else {
      $youtubechannel_video_limit = $config->get('youtubechannel_video_limit');
    }
	
	  if(empty($config->get('youtubechannel_video_width'))) {
      $youtubechannel_video_width = 200;
    }
    else {
      $youtubechannel_video_width = $config->get('youtubechannel_video_width');
    }
	
	  if(empty($config->get('youtubechannel_video_height'))) {
      $youtubechannel_video_height = 150;
    }
    else {
      $youtubechannel_video_height = $config->get('youtubechannel_video_height');
    }
    
    $form['youtubechannel'] = array(
      '#type' => 'fieldset',
      '#title' => t('Youtube channel settings'),
      '#collapsible' => FALSE,
    );

    $form['youtubechannel']['youtubechannel_api_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Youtube Google API Key'),
      '#size' => 40,
      '#default_value' => $youtubechannel_api_key,
      '#required' => TRUE,
      '#description' => t('Your YouTube Google API key from your developer' . 'console. See the README.txt for more details.'),
    );

    $form['youtubechannel']['youtubechannel_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Youtube Channel ID'),
      '#size' => 40,
      '#default_value' => $youtubechannel_id,
      '#required' => TRUE,
      '#description' => t('The youtube channel ID you want to get the videos.'),
    );

    $form['youtubechannel']['youtubechannel_video_limit'] = array(
      '#type' => 'textfield',
      '#title' => t('Youtube Channel video limit'),
      '#size' => 40,
      '#default_value' => $youtubechannel_video_limit,
      '#required' => TRUE,
      '#description' => t('Number of videos to be shown from youtube channel (max 50).'),
    );

    $form['youtubechannel']['youtubechannel_video_width'] = array(
      '#type' => 'textfield',
      '#title' => t('Youtube Channel video width'),
      '#size' => 40,
      '#default_value' => $youtubechannel_video_width,
      '#required' => TRUE,
      '#description' => t('Max width to youtube video. In px'),
    );

    $form['youtubechannel']['youtubechannel_video_height'] = array(
      '#type' => 'textfield',
      '#title' => t('Youtube Channel video height'),
      '#size' => 40,
      '#default_value' => $youtubechannel_video_height,
      '#required' => TRUE,
      '#description' => t('Max height to youtube video. In px'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('youtubechannel.settings')->set('youtubechannel_api_key', $form_state->getValue('youtubechannel_api_key'))
	    ->set('youtubechannel_id', $form_state->getValue('youtubechannel_id'))
	    ->set('youtubechannel_video_limit', $form_state->getValue('youtubechannel_video_limit'))
	    ->set('youtubechannel_video_width', $form_state->getValue('youtubechannel_video_width'))
	    ->set('youtubechannel_video_height', $form_state->getValue('youtubechannel_video_height'))->save();
    parent::submitForm($form, $form_state);
  }
}
