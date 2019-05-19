<?php

namespace Drupal\youtube_gallery\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\youtube_gallery\Controller\UploadVideo;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Upload video form.
 */
class UploadVideoForm extends FormBase {

  protected $upload;
  protected $files;

  /**
   * Creates constructor and define class.
   */
  public function __construct(UploadVideo $configuration, EntityStorageInterface $entityStorage) {

    $this->upload = $configuration;
    $this->files = $entityStorage;
  }

  /**
   * Initiating dependency class.
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('youtube_gallery.do_upload'),
      $container->get('entity.manager')->getStorage('file')
    );

  }

  /**
   * Returning form id.
   *
   * @{inheritdoc}
   */
  public function getFormId() {

    return 'upload_youtube_video';
  }

  /**
   * Build an upload form.
   *
   * @{inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $path = drupal_realpath('libraries/google-api-php-client/vendor/autoload.php');

    $error = "";

    if (!file_exists($path)) {

      $error = "<br><h4>Google Libraries not found.</h4>";
      $error .= "Follow the steps for installing libraries.<br>";
      $error .= "<br><b>STEP 1: </b>Run drush command: <b>drush ytg-libraries</b>";
      $error .= "<br><b>STEP 2: </b>Go to the library folder google-api-php-client and run composer install";

      return [
        '#type' => 'markup',
        '#markup' => $error,
      ];

    }

    $form['panel'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Upload Video To Youtube'),
      '#open' => TRUE,
    ];

    $form['panel']['title'] = [
      '#type' => 'textfield',
      '#title'  => $this->t('Enter Title'),
    ];
    $form['panel']['description'] = [
      '#type' => 'textarea',
      '#title'  => $this->t('Enter Description'),
    ];
    $form['panel']['tags'] = [
      '#type' => 'textfield',
      '#title'  => $this->t('Add Tags'),
      '#description'  => $this->t('add tags like drupal, php, website add with comma ( , )'),
    ];
    $form['panel']['categaries'] = [
      '#type' => 'select',
      '#title'  => $this->t('Select Category'),
      '#description'  => $this->t('Select category in which your video to be displayed.'),
      '#options'  => [

        '2' => 'Autos & Vehicles',
        '1' => 'Film & Animation',
        '10' => 'Music',
        '15' => 'Pets & Animals',
        '17' => 'Sports',
        '18' => 'Short Movies',
        '19' => 'Travel & Events',
        '20' => 'Gaming',
        '21' => 'Videoblogging',
        '22' => 'People & Blogs',
        '23' => 'Comedy',
        '24' => 'Entertainment',
        '25' => 'News & Politics',
        '26' => 'Howto & Style',
        '27' => 'Education',
        '28' => 'Science & Technology',
        '29' => 'Nonprofits & Activism',
        '30' => 'Movies',
        '31' => 'Anime/Animation',
        '32' => 'Action/Adventure',
        '33' => 'Classics',
        '34' => 'Comedy',
        '35' => 'Documentary',
        '36' => 'Drama',
        '37' => 'Family',
        '38' => 'Foreign',
        '39' => 'Horror',
        '40' => 'Sci-Fi/Fantasy',
        '41' => 'Thriller',
        '42' => 'Shorts',
        '43' => 'Shows',
        '44' => 'Trailers',

      ],
      '#default_value'  => 22,
    ];

    $form['panel']['upload'] = [
      '#type' => 'managed_file',
      '#title'  => $this->t('Upload Video'),
      '#required' => TRUE,
      '#upload_validators' => [
        'file_validate_extensions' => ['mp4 mkv'],
      ],
    ];

    $form['panel']['submit'] = [
      '#type' => 'submit',
      '#value'  => $this->t('Upload Video'),
      '#button_type' => 'primary',
    ];

    return $form;

  }

  /**
   * Form validation.
   *
   * @{inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $file = $form_state->getValue('upload');

    if (is_null($file)) {

      $form_state->setErrorByName('upload', $this->t('Upload field required..'));

    }
  }

  /**
   * Processing when form has been submitted.
   *
   * @{inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $file = $form_state->getValue('upload');

    $title = $form_state->getValue('title');
    $desc = $form_state->getValue('description');
    $tags = $form_state->getValue('tags');
    $category = $form_state->getValue('categaries');

    $video = $this->files->load($file[0]);

    $file_uri = $video->getFileUri();

    // Upload process.
    $this->upload->youtubeUpload($title, $desc, $tags, $category, $file_uri);

  }

}
