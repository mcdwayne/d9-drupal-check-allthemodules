<?php

namespace Drupal\s3fs_plus\Plugin\EntityBrowser\Widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\WidgetBase;
use Drupal\entity_browser\WidgetValidationManager;
use Drupal\media_entity\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\Entity\File;

/**
 * Uses S3 to provide File listing in a browser's widget.
 *
 * @EntityBrowserWidget(
 *   id = "s3view",
 *   label = @Translation("S3 View"),
 *   description = @Translation("Fetches all files from S3 bucket."),
 *   auto_select = FALSE
 * )
 */
class S3View extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.entity_browser.widget_validation'),
      $container->get('current_user')
    );
  }

  /**
   * Constructs a new user object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\entity_browser\WidgetValidationManager $validation_manager
   *   The Widget Validation Manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    // Fetch all files from S3 bucket.
    $s3fs_config = \Drupal::config('s3fs.settings');
    $s3fs = get_client($s3fs_config);
    $s3_files = $s3fs->listObjectsV2(['Bucket' => $s3fs_config->get('bucket')]);

    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);
    $i = 0;
    foreach ($s3_files['Contents'] as $s3_file) {
      $fileType = $this->getFileType($s3_file['Key']);
      $fileName = 's3fs://' . $s3_file['Key'];
      if ($fileType == 'document') {
        // @todo: Get generic file.
        $fileName = 'public://media-icons/generic/document.png';
      }
      elseif ($fileType == 'video') {
        // @todo: Get generic file.
        $fileName = 'public://media-icons/generic/video.png';
      }
      $render = [
        '#theme' => 'image',
        '#uri' => $fileName,
        '#attributes' => [
          'width' => '100px',
          'height' => '100px',
          'key' => $s3_file['Key'],
          'style' => 'padding:10px;',
        ],
        '#prefix' => '<span>',
        '#suffix' => '</span>',
      ];
      $form['s3_' . $i] = [
        '#markup' => \Drupal::service('renderer')->render($render),
      ];
      $i++;
    }

    $form['selected_file'] = [
      '#type' => 'hidden',
      '#value' => isset($form_state->getUserInput()['selected_file']) ? $form_state->getUserInput()['selected_file'] : '',
    ];

    $form['#attached']['library'] = ['s3fs_plus/s3view'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array &$form, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();
    if (isset($user_input['selected_file']) && empty($user_input['selected_file'])) {
      $form_state->setError($form['widget'], $this->t('Please select a file.'));
    }

    // If there weren't any errors set, run the normal validators.
    if (empty($form_state->getErrors())) {
      parent::validate($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntities(array $form, FormStateInterface $form_state) {
    $selected_file = $form_state->getUserInput()['selected_file'];

    $media = [];
    if (!empty($selected_file)) {
      // Search in media, if this file exists.
      $media = entity_load_multiple_by_properties('media', array('name' => $selected_file));
      if (!count($media)) {
        // Check if File exists in Drupal.
        $file = entity_load_multiple_by_properties('file', array('uri' => 's3fs://' . $selected_file));
        if (!count($file)) {
          // Save the file in Drupal.
          $newFile = File::create([
            'uri' => 's3fs://' . $selected_file,
            'uid' => $this->currentUser->id(),
            'status' => FILE_STATUS_PERMANENT,
          ]);
          $newFile->save();
          $file[] = $newFile;
        }
        // Create Media.
        $newMedia = Media::create([
          'bundle' => $this->getFileType($selected_file),
          'name' => $selected_file,
          'image' => [
            'target_id' => current($file)->id(),
          ],
          'uid' => $this->currentUser->id(),
        ]);

        $newMedia->save();
        $media[] = $newMedia;
      }
    }
    return $media;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    $entities = $this->prepareEntities($form, $form_state);
    $this->selectEntities($entities, $form_state);
  }

  /**
   * Fetch the type of file.
   *
   * @param string $fileName
   *   The filename for which type is to be fetched.
   *
   * @return bool|string
   *   The type of file OR true if filename is provided, else false.
   */
  protected function getFileType($fileName) {
    // @todo: Get a way to generalize the file types.
    if (!empty($fileName)) {
      $fileName = explode('.', $fileName);
      $fileExt = array_pop($fileName);
      if (in_array($fileExt, ['gif', 'ico', 'jpeg', 'jpg', 'png', 'svg'])) {
        return 'image';
      }
      elseif (in_array($fileExt, [
        'css',
        'csv',
        'dmg',
        'doc',
        'docx',
        'eot',
        'htc',
        'htm',
        'html',
        'jar',
        'java',
        'js',
        'json',
        'less',
        'mp3',
        'otf',
        'pdf',
        'ppsx',
        'ppt',
        'pptx',
        'rtf',
        'ttf',
        'txt',
        'woff',
        'xls',
        'xlsm',
        'xlsx',
        'xml',
        'zip',
      ])) {
        return 'document';
      }
      elseif (in_array($fileExt, ['mov', 'mp4', 'wmv', 'avi', 'flv'])) {
        return 'video';
      }
      return TRUE;
    }
    return FALSE;
  }

}
