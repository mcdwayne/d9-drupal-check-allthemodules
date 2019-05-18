<?php

namespace Drupal\background_video\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

class BackgroundVideoSettingsForm extends ConfigFormBase {
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  public function getFormId() {
    return 'background_video_config_form';
  }

  protected function getEditableConfigNames() {
    return ['config.background_video'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('config.background_video');

    $form['background_video_source_mp4'] = [
      '#title' => $this->t('.mp4 Video'),
      '#type' => 'managed_file',
      '#description' => $this->t('Please upload a .mp4 file. MP4 adds support for Safari & IE.'),
      '#default_value' => $config->get('background_video_source_mp4', NULL),
      '#upload_validators' => ['file_validate_extensions' => ['mp4']],
      '#upload_location' => 'public://background_video',
      '#required' => TRUE,
    ];
    $form['background_video_source_webm'] = [
      '#title' => $this->t('.webm Video'),
      '#type' => 'managed_file',
      '#description' => $this->t('Please upload a .webm file. WEBM adds support for Chrome, Firefox, & Opera.'),
      '#required' => TRUE,
      '#default_value' => $config->get('background_video_source_webm', NULL),
      '#upload_validators'  => ['file_validate_extensions' => ['webm']],
      '#upload_location' => 'public://background_video',
    ];
    $form['background_video_source_ogv'] = [
      '#title' => $this->t('.ogv Video'),
      '#type' => 'managed_file',
      '#description' => $this->t('Provide upload a .ogg video. OGV adds support to different browsers.'),
      '#required' => TRUE,
      '#default_value' => $config->get('background_video_source_ogv', NULL),
      '#upload_validators'  => ['file_validate_extensions' => ['ogv']],
      '#upload_location' => 'public://background_video',
    ];
    $form['background_video_id'] = [
      '#title' => $this->t('ID/Class Name'),
      '#type' => 'textfield',
      '#description' => $this->t('Provide the specific ID/Class to which you want to add the background video. Prepend # with ID or . with class'),
      '#required' => TRUE,
      '#default_value' => $config->get('background_video_id', 'body'),
    ];
    $form['background_video_control_position'] = [
      '#title' => $this->t('Control Position'),
      '#type' => 'textfield',
      '#description' => $this->t('Provide the specific ID where controls like Play/Pause and Mute/Unmute are placed. Leave blank if you do not want that user can control the background video.'),
      '#default_value' => $config->get('background_video_control_position', '#footer'),
    ];
    $form['background_video_source_poster'] = [
      '#title' => $this->t('Video Poster'),
      '#type' => 'managed_file',
      '#description' => $this->t('Provide the poster for the video.'),
      '#required' => TRUE,
      '#default_value' => $config->get('background_video_source_poster', NULL),
      '#upload_validators'  => ['file_validate_extensions' => ['gif jpg jpeg png']],
      '#upload_location' => 'public://background_video',
    ];
    $form['background_video_loop'] = [
      '#title' => $this->t('Loop Video'),
      '#type' => 'checkbox',
      '#description' => $this->t('Select the checkbox if you want to play the video in the loop.'),
      '#default_value' => $config->get('background_video_loop', 1),
    ];
    $form['background_video_autoplay'] = [
      '#title' => $this->t('Autoplay Video'),
      '#type' => 'checkbox',
      '#description' => $this->t('Select the checkbox if you want to autpplay the video when the page is loaded.'),
      '#default_value' => $config->get('background_video_autoplay', 1),
    ];
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('config.background_video');
    $values = $form_state->getValues();
    $config->set('background_video_source_mp4', $values['background_video_source_mp4'])
          ->set('background_video_source_webm', $values['background_video_source_webm'])
          ->set('background_video_source_ogv', $values['background_video_source_ogv'])
          ->set('background_video_id', $values['background_video_id'])
          ->set('background_video_control_position', $values['background_video_control_position'])
          ->set('background_video_autoplay', $values['background_video_autoplay'])
          ->set('background_video_loop', $values['background_video_loop'])
          ->set('background_video_source_poster', $values['background_video_source_poster'])
          ->save();
    $file_types = ['mp4', 'ogv', 'webm', 'poster'];
    foreach ($file_types as $ftype) {
      $fid = $values['background_video_source_' . $ftype][0];
      $file = File::load($fid);
      $file->status = FILE_STATUS_PERMANENT;
      $file->save();
      $file_usage = \Drupal::service('file.usage');
      $file_usage->add($file, 'background_video', 'file', $fid);
      parent::submitForm($form, $form_state);
    }
  }
}
