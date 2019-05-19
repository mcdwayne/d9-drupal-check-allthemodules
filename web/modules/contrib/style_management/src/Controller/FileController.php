<?php

namespace Drupal\style_management\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FileController.
 *
 * @package object|Drupal\style_management\Controller
 *   The file controller.
 */
class FileController extends ControllerBase implements ContainerInjectionInterface, FileControllerInterface {

  /**
   * List of processable file with two permitted extension less & scss.
   *
   * @var array
   */
  private $processableFiles;

  /**
   * {@inheritdoc}
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  protected $configFactory;

  /**
   * Implements FileSystemInterface.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;
  /**
   * FileController constructor.
   *
   * @param object|\Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param object|\Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param object|\Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config service.
   * @param object|\Drupal\Core\File\FileSystemInterface $fileSystem
   *   Loading interface for file service.
   */
  public function __construct(MessengerInterface $messenger, StateInterface $state, ConfigFactoryInterface $configFactory, FileSystemInterface $fileSystem) {

    $this->processableFiles = ['less', 'scss'];

    $this->messenger = $messenger;
    $this->state = $state;
    $this->configFactory = $configFactory;
    $this->fileSystem = $fileSystem;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('config.factory'),
      $container->get('state'),
      $container->get('file_system')
    );
  }

  /**
   * Check if file is processable.
   *
   * @param array $config
   *   Array with current configuration.
   * @param string $file_path
   *   String with current file path.
   */
  public function isProcessable(array &$config, string $file_path) {
    $file_type = substr($file_path, -4);
    $file_type_to_lower = strtolower($file_type);

    if (in_array($file_type_to_lower, $this->processableFiles)) {

      /*
       * Verify if confie exist, if not exist provide a default configuration
       * watch => boolean | watch file true or false
       * aggregate => boolean | aggregate file in a single file compiled
       * alter_variables => array | replace value of values present on file
       * before compile
       */
      $empty_config = '';
      if (!isset($config['processable_file'][$file_type_to_lower][$file_path]) && empty($config['processable_file'][$file_type_to_lower][$file_path])) {
        switch ($file_type_to_lower) {
          case 'less':
            $empty_config = [
              'watch' => TRUE,
              'destination_path' => '',
              'aggregate' => FALSE,
              'alter_variables' => [],
            ];
            break;

          case 'scss':
            $empty_config = [
              'watch' => TRUE,
              'destination_path' => '',
            ];
            break;

        }
        $config['processable_file'][$file_type_to_lower][$file_path] = $empty_config;
      }
    }
  }

  /**
   * Extract Compiled file name by source path.
   *
   * @param string $source
   *   Full path of current file.
   *
   * @return mixed
   *   Return full string with value.
   */
  public function getCompiledFileName($source) {

    $source_to_lowercase = strtolower($source);

    $fileType = substr($source_to_lowercase, -4);

    $exploded_path = explode('/', $source);
    $current_file_name = end($exploded_path);

    return str_replace($fileType, 'css', $current_file_name);
  }

  /**
   * Write file in specific folder.
   *
   * @param array $files
   *   Array with file info and content.
   *
   * @return bool
   *   Return state of writing.
   */
  public function writeFiles(array $files = []) {
    $state = $this->state;
    $override_config_less = $this->configFactory->get('style_management.lessfiles');
    $override_config_scss = $this->configFactory->get('style_management.scssfiles');
    $default_config = $this->configFactory->get('style_management.settings');

    if (count($files) > 0) {
      foreach ($files as $info) {
        try {

          // Make File id.
          $file_id = MainController::makeFileId($info['source']);

          // Get Overrided destination path.
          $file_type = $info['file_type'];
          switch ($file_type) {
            case 'scss':
              $destination_path = $override_config_scss->get('setting.destination_path--' . $file_id);
              break;

            case 'less':
              $destination_path = $override_config_less->get('setting.destination_path--' . $file_id);
              break;
          }

          if ((empty($destination_path)) || ($destination_path == NULL)) {

            // Get Default destination path.
            $config = 'setting.' . $info["file_type"] . '_default_destination_folder';
            $destination_path = $default_config->get($config);
          }

          // Show error if foldes isn't writable.
          $fdp = $this->fileSystem->realpath($destination_path);
          if (file_prepare_directory( $fdp, FILE_CREATE_DIRECTORY)) {
            if (!is_writable($fdp)) {

              $error = 'Style Management: ' . $this->t('Destination Folder is not writable, please verify permission on') . ' ' . $destination_path;
              $this->messenger->addError($error);
              return [];
            }
          }
          if (file_prepare_directory($destination_path, FILE_CREATE_DIRECTORY)) {
            $new_file_name = $this->getCompiledFileName($info['source']);

            $destination = $destination_path . '/' . $new_file_name;
            if (!empty($info['content'])) {
              file_unmanaged_save_data($info['content'], $destination, FILE_EXISTS_REPLACE);
            }
            else {
              file_unmanaged_delete($destination);
            }

            // Write complete uri of compiled file in config.
            if ($destination == !'') {
              $current_config = $state->get('style_management.config', '');
              $source = $info['source'];
              $current_config['processable_file'][$file_type][$source]['compiled_file_path'] = $destination;
              $state->set('style_management.config', $current_config);
            }
          }
          else {
            $error = $this->t('Not possible create folder: "%destination" for file: "%file"', ['%destination' => $info['destination'], '%file' => $info['source']]);
            $this->messenger->addError($error);
          }
        }
        catch (\Exception $exception) {
          $error = $this->t('Impossible to compiler the file css : %exception', ['%exception' => "$exception"]);
          $this->messenger->addError($error);
        }
      }
      return TRUE;
    }
    return FALSE;
  }

}
