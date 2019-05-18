<?php

namespace Drupal\dtuber\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Test upload form for Dtuber.
 */
class TestUploadForm extends FormBase {

  protected $dtuberYtService;

  /**
   * {@inheritdoc}
   */
  public function __construct($dtuberYoutube) {
    $this->dtuberYtService = $dtuberYoutube;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('dtuber_youtube_service'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dtuber_test_upload_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Video Title'),
      '#description' => $this->t('Provide Title for this Video.'),
      '#required' => TRUE,
    );
    $form['description'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Video Description'),
    );
    $allowed_exts = array('mov mp4 avi mkv ogv webm 3gp flv');
    $exts = implode(', ', explode(' ', $allowed_exts[0]));
    $video_desc = $this->t('Allowed Extensions: :extensions', [':extensions' => $exts]);
    $form['video'] = array(
      '#type' => 'managed_file',
      '#title' => $this->t('Upload a Video'),
      '#description' => $video_desc,
      '#upload_location' => 'public://dtuber_files',
      '#upload_validators' => array(
        'file_validate_extensions' => $allowed_exts,
      ),
      '#required' => TRUE,
    );
    $form['tags'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Video Tags'),
      '#description' => $this->t('Enter comma separated tags'),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Upload to YouTube'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $file = $form_state->getValue('video');
    $file = File::load($file[0]);
    $path = file_create_url($file->getFileUri());
    global $base_url;

    $options = array(
      'path' => str_replace($base_url, '', $path),
      'title' => $form_state->getValue('title'),
      'description' => $form_state->getValue('description'),
      'tags' => explode(',', $form_state->getValue('tags')),
    );
    $response = $this->dtuberYtService->uploadVideo($options);
    if ($response['status'] != 'OK') {
      drupal_set_message($this->t('Unable to upload Video.'), 'error');
    }
  }

}
