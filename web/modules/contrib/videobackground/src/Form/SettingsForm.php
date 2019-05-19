<?php

namespace Drupal\videobackground\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Form controller for video background settings forms.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'videobackground_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public static function save($fid) {
    // Call file usage service and load file.
    $file_usage = \Drupal::service('file.usage');
    $file = File::load($fid);

    // Set file status permanent.
    if (!$file->isPermanent()) {
      $file->setPermanent();
    }
    // Check file usage , if it's empty, add new entry.
    $usage = $file_usage->listUsage($file);
    if (empty($usage)) {
      $file_usage->add($file, 'videobackground', 'vide', $fid);
    }
    $file->save();
    $uri = $file->getFileUri();
    $path = file_create_url($uri);
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public static function getVideInfo($fid) {
    if (empty($fid)) {
      return;
    }
    $path = self::save($fid);
    return $fid . '-' . $path;
  }

  /**
   * {@inheritdoc}
   */
  public static function getFileId($data) {
    if (empty($data)) {
      return;
    }
    $fid = explode('-', $data);
    return $fid[0];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('videobackground.settings')
      ->set('included_selectors', $form_state->getValue('included_selectors'))
      ->set('mp4', $this->getVideInfo($form_state->getValue('mp4')[0]))
      ->set('ogv', $this->getVideInfo($form_state->getValue('ogv')[0]))
      ->set('webm', $this->getVideInfo($form_state->getValue('webm')[0]))
      ->set('poster', $this->getVideInfo($form_state->getValue('poster')[0]))
      ->set('volume', $form_state->getValue('volume'))
      ->set('playbackrate', $form_state->getValue('playbackrate'))
      ->set('muted', $form_state->getValue('muted'))
      ->set('loop', $form_state->getValue('loop'))
      ->set('autoplay', $form_state->getValue('autoplay'))
      ->set('position', $form_state->getValue('position'))
      ->set('postertype', $form_state->getValue('postertype'))
      ->set('resizing', $form_state->getValue('resizing'))
      ->set('bgcolor', $form_state->getValue('bgcolor'))
      ->set('classname', $form_state->getValue('classname'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['videobackground.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('videobackground.settings');

    $form['selectors'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Visibility settings'),
    ];

    $form['selectors']['included_selectors'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Include selectors'),
      '#description' => $this->t('CSS selectors (one per line).'),
      '#default_value' => $config->get('included_selectors'),
    ];

    $form['video_background_location'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Video background location'),
    ];

    $form['video_background_location']['mp4'] = [
      '#type' => 'managed_file',
      '#upload_location'  => 'public://vide',
      '#multiple' => FALSE,
      '#required' => TRUE,
      '#description' => $this->t('Allowed extensions: mp4'),
      '#upload_validators' => [
        'file_validate_extensions'  => ['mp4'],
      ],
      '#title' => $this->t('Upload an mp4 file for video background.'),
      '#default_value' => !empty($this->getFileId($config->get('mp4'))) ? array($this->getFileId($config->get('mp4'))) : '',
    ];

    $form['video_background_location']['ogv'] = [
      '#type' => 'managed_file',
      '#upload_location'  => 'public://vide',
      '#multiple' => FALSE,
      '#required' => TRUE,
      '#description' => $this->t('Allowed extensions: ogv'),
      '#upload_validators' => [
        'file_validate_extensions'  => ['ogv'],
      ],
      '#title' => $this->t('Upload an ogv file for video background.'),
      '#default_value' => !empty($this->getFileId($config->get('ogv'))) ? array($this->getFileId($config->get('ogv'))) : '',
    ];

    $form['video_background_location']['webm'] = [
      '#type' => 'managed_file',
      '#upload_location'  => 'public://vide',
      '#multiple' => FALSE,
      '#required' => TRUE,
      '#description' => $this->t('Allowed extensions: webm'),
      '#upload_validators' => [
        'file_validate_extensions'  => ['webm'],
      ],
      '#title' => $this->t('Upload an webm file for video background.'),
      '#default_value' => !empty($this->getFileId($config->get('webm'))) ? array($this->getFileId($config->get('webm'))) : '',
    ];

    $form['video_background_location']['poster'] = [
      '#type' => 'managed_file',
      '#upload_location'  => 'public://vide',
      '#multiple' => FALSE,
      '#required' => TRUE,
      '#description' => $this->t('Allowed extensions: jpg jpeg gif png'),
      '#upload_validators' => [
        'file_validate_extensions'  => ['jpg jpeg gif png'],
      ],
      '#title' => $this->t('Upload an jpg jpeg gif png file for poster.'),
      '#default_value' => !empty($this->getFileId($config->get('poster'))) ? array($this->getFileId($config->get('poster'))) : '',
    ];

    $form['video_background_location']['content'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Examples'),
    ];

    $rows = [
      ['path/'],
      ['├── to/'],
      ['│  ├── video.mp4'],
      ['│  ├── video.ogv'],
      ['│  ├── video.webm'],
      ['│  ├── video .jpg, .png or .gif extension'],
    ];

    $form['video_background_location']['content'] = [
      '#type' => 'table',
      '#header' => [$this->t('Example')],
      '#rows' => $rows,
    ];

    $form['video_background'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Video background options'),
    ];

    $form['video_background']['volume'] = [
      '#type' => 'select',
      '#title' => $this->t('Volume'),
      '#options' => [0, 1],
      '#default_value' => $config->get('volume'),
      '#description' => $this->t('By default highest volume level 1 and 0 is silent (same as mute).'),
    ];

    $form['video_background']['playbackrate'] = [
      '#type' => 'select',
      '#title' => $this->t('PlaybackRate'),
      '#options' => [
        1 => $this->t('1.0'),
        2 => $this->t('2.0'),
      ],
      '#default_value' => $config->get('playbackrate'),
      '#description' => $this->t('The playbackRate property sets or returns the current playback speed of the audio/video.'),
    ];

    $form['video_background']['content'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Examples'),
    ];

    $rows = [
      ['playbackspeed', $this->t('Indicates the current playback speed of the audio/video.')],
      ['', $this->t('Example values:')],
      ['', $this->t('1.0 is normal speed')],
      ['', $this->t('2.0 is double speed (faster)')],
    ];

    $form['video_background']['content'] = [
      '#type' => 'table',
      '#header' => [$this->t('Value'), $this->t('Description')],
      '#rows' => $rows,
    ];

    $form['video_background']['muted'] = [
      '#type' => 'radios',
      '#title' => $this->t('Muted'),
      '#default_value' => $config->get('muted'),
      '#options' => [0 => $this->t('False'), 1 => $this->t('True')],
      '#description' => $this->t('By default video is muted'),
    ];

    $form['video_background']['loop'] = [
      '#type' => 'radios',
      '#title' => $this->t('Loop'),
      '#default_value' => $config->get('loop'),
      '#options' => [0 => $this->t('False'), 1 => $this->t('True')],
    ];

    $form['video_background']['autoplay'] = [
      '#type' => 'radios',
      '#title' => $this->t('Autoplay'),
      '#default_value' => $config->get('autoplay'),
      '#options' => [0 => $this->t('False'), 1 => $this->t('True')],
    ];

    $form['video_background']['position'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Position'),
      '#default_value' => $config->get('position'),
      '#description' => $this->t('Similar to the CSS `background-position` property.'),
    ];

    $form['video_background']['postertype'] = [
      '#type' => 'select',
      '#title' => $this->t('PosterType'),
      '#options' => ['detect' => $this->t('detect')],
      '#default_value' => $config->get('postertype'),
      '#description' => $this->t('Poster image type. "detect" — auto-detection; "none" — no poster; "jpg", "png", "gif",... - extensions.'),
    ];

    $form['video_background']['resizing'] = [
      '#type' => 'radios',
      '#title' => $this->t('Resizing'),
      '#default_value' => $config->get('resizing'),
      '#options' => [0 => $this->t('False'), 1 => $this->t('True')],
    ];

    $form['video_background']['bgcolor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Background-color'),
      '#default_value' => $config->get('bgcolor'),
      '#description' => $this->t('Allow custom background-color for Vide div.'),
    ];

    $form['video_background']['classname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('className'),
      '#default_value' => $config->get('classname'),
      '#description' => $this->t('Add custom CSS class to Vide div.'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
