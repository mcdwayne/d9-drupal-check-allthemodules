<?php

namespace Drupal\style_management\Controller;

use Drupal\Core\Controller\ControllerBase;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\style_management\CompilerServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MainController.
 */
class MainController extends ControllerBase implements ContainerInjectionInterface, MainControllerInterface {

  private $config;

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
  protected $fileController;

  /**
   * {@inheritdoc}
   */
  protected $compilerService;

  /**
   * MainController constructor.
   *
   * @param object|\Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param object|\Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param object|\Drupal\style_management\Controller\FileControllerInterface $fileController
   *   The File service.
   * @param object|\Drupal\style_management\CompilerServiceInterface $compilerService
   *   The Compiler service.
   */
  public function __construct(MessengerInterface $messenger, StateInterface $state, FileControllerInterface $fileController, CompilerServiceInterface $compilerService) {
    $this->messenger = $messenger;

    // State.
    $this->state = $state;

    // File Controller.
    $this->fileController = $fileController;

    // Compiler Service.
    $this->compilerService = $compilerService;

    // Config.
    $this->config = $state->get('style_management.config', []);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('state'),
      $container->get('style_management.file_controller'),
      $container->get('style_management.compiler')
    );
  }

  /**
   * This method rebuild all css.
   *
   * @param array $css
   *   Array with all css info.
   */
  public function rebuildTree(array &$css) {

    // Get all style's path.
    $files_path = array_keys($css);

    // Generate Map of processable file.
    foreach ($files_path as $path) {

      // This method initialize check if file is processable.
      $this->fileController->isProcessable($this->config, $path);
    }

    // Set config.
    $state = $this->state;
    $state->set('style_management.config', $this->config);
  }

  /**
   * Public method to compile all files and write it.
   */
  public function build() {
    $files = $this->compilerService->compileAll();
    $this->writeFiles($files);
  }

  /**
   * Do Alter css register.
   *
   * @param array $css
   *   The original $css from hook_css_alter().
   */
  public function alterCss(array &$css) {
    $processable_files = (isset($this->config['processable_file']) && !empty($this->config['processable_file'])) ? $this->config['processable_file'] : [];
    foreach ($processable_files as $files) {
      $this->override($css, $files);
    }
  }

  /**
   * Private method to write file on drupal.
   *
   * @param array $files
   *   Array with fileinfo.
   */
  private function writeFiles(array $files = []) {
    $this->fileController->writeFiles($files);
  }

  /**
   * Alter $css Array with new path of compiled file.
   *
   * @param array $css
   *   Original $css array with all style file.
   * @param array $files
   *   Array with compiler file info.
   */
  private function override(array &$css, array $files) {
    foreach ($files as $source => $info) {

      // Check if file is in watch mode.
      $watch = (isset($info['watch']) && !empty($info['watch'])) ? $info['watch'] : FALSE;

      // Get Compiled file path.
      $compiled_file_path = (isset($info['compiled_file_path']) && !empty($info['compiled_file_path'])) ? $info['compiled_file_path'] : FALSE;

      if (isset($css[$source]) && !empty($css[$source])) {
        if ($watch  && ($compiled_file_path !== FALSE)) {
          $current_source_file_info = $css[$source];

          // Unset current file on style info.
          unset($css[$source]);

          // Make new config of compiled file.
          $newInfo = $current_source_file_info;
          $newInfo['data'] = $compiled_file_path;
          $css[$compiled_file_path] = $newInfo;
        }
      }
      else {
        // Delete from cache source file if not exist.
        unset($css[$source]);
      }
    }
  }

  /**
   * Use this method to generate a single array of variable name and value.
   *
   * @param array $variables_file
   *   Accept array value from file.
   * @param array $variables_config
   *   Accept array value from config.
   * @param array $option
   *   Specify type of return value, if possible return it to json format.
   *
   * @return string|array
   *   An array with single variables and value.
   */
  public static function mergeVariables(array $variables_file, array $variables_config, array $option = ['jsonencode' => FALSE]) {
    $merged_variables = $variables_file;
    $keys_variables = array_keys($variables_file);

    if (count($merged_variables) > 0) {
      $tmp = [];
      foreach ($keys_variables as $key) {
        $tmp[$key]['from_file'] = trim($merged_variables[$key]);
        $tmp[$key]['from_config'] = '';
      }
      $merged_variables = $tmp;
    }

    if (count($variables_config) > 0) {

      foreach ($variables_config as $key => $value) {
        if (isset($merged_variables[$key]) && !empty($merged_variables[$key])) {
          $merged_variables[$key]['from_config'] = $value;
        }
        else {
          $merged_variables[$key]['from_file'] = '';
          $merged_variables[$key]['from_config'] = $value;
        }
      }
    }

    if ($option['jsonencode']) {
      return json_encode($merged_variables);
    }
    return $merged_variables;
  }

  /**
   * Convert full path to fileId, is necessary to save info on config system.
   *
   * @param string $path
   *   Full path of file.
   * @param bool $reverse
   *   Decompile fileId to normal path.
   *
   * @return mixed
   *   Convert path of file to fileId, src/folder/file.scss to
   *   src-folder-file_scss
   *
   * @TODO test different case of $reverse.
   */
  public static function makeFileId(string $path, $reverse = FALSE) {

    if ($reverse) {
      $string = str_replace('-', '/', $path);
      $string = str_replace('_', '.', $string);
    }
    else {

      $string = str_replace('/', '-', $path);
      $string = str_replace('.', '_', $string);

    }
    return $string;
  }

}
