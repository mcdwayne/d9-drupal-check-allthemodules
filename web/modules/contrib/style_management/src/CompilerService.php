<?php

namespace Drupal\style_management;

use Drupal\style_management\Controller\MainController;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

use scssc;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CompilerService.
 */
class CompilerService extends ControllerBase implements ContainerInjectionInterface, CompilerServiceInterface {

  private $config;
  private $processableFile = ['less', 'scss'];
  private $processableFileConfig;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Implements StateInterface.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Implements ConfigFactoryInterface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Implements FileSystemInterface.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * CompilerService constructor.
   *
   * @param object|\Drupal\Core\Messenger\MessengerInterface $messenger
   *   Loading interface for messenger service.
   * @param object|\Drupal\Core\State\StateInterface $state
   *   Loading interface for statement service.
   * @param object|\Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Loading interface for config service.
   * @param object|\Drupal\Core\File\FileSystemInterface $fileSystem
   *   Loading interface for file service.
   */
  public function __construct(MessengerInterface $messenger, StateInterface $state, ConfigFactoryInterface $configFactory, FileSystemInterface $fileSystem) {
    $this->messenger = $messenger;
    $this->state = $state;
    $this->configFactory = $configFactory;
    $this->fileSystem = $fileSystem;

    // Get all configuration.
    $this->config = $state->get('style_management.config', '');

    // Get All processable file.
    $this->processableFileConfig = (isset($this->config['processable_file']) && !empty($this->config['processable_file'])) ? $this->config['processable_file'] : [];
  }

  /**
   * Load info from container.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Require container.
   *
   * @return \Drupal\style_management\CompilerService
   *   Return service form container.
   */
  public static function create(ContainerInterface $container) {
    // Load the service required to construct this class.
    return new static(
      $container->get('messenger'),
      $container->get('state'),
      $container->get('config.factory'),
      $container->get('file_system')
    );
  }

  /**
   * Compile All Files.
   */
  public function compileAll() {
    $less = [];
    $scss = [];
    foreach ($this->processableFileConfig as $type => $config) {
      if (in_array($type, $this->processableFile)) {
        switch ($type) {
          case 'less':

            // Compile less files parsing config.
            $less = $this->compileLess($config);

            break;

          case 'scss':

            // Compile less files parsing config.
            $scss = $this->compileScss($config);

            break;
        }
      }
    }
    return array_merge($less, $scss);
  }

  /**
   * Run compiling Scss.
   *
   * @param array $config
   *   Parse configuration.
   *
   * @return array
   *   Array with files info.
   */
  private function compileScss(array $config) {
    $files = [];
    $options = [];
    $this->getOptionsScss($options);

    // Scss Parser.
    $parser = new scssc();

    foreach ($config as $source => $info) {
      if ((isset($info['watch'])) && ($info['watch'] === TRUE)) {

        // Get Realpath.
        $real_path = $this->fileSystem->realpath($info['destination_path']);

        // Remove subfolder, start at path by DRUPAL_ROOT.
        $path_from_root = str_replace(DRUPAL_ROOT, '', $real_path);
        $path_from_root = substr($path_from_root, 1);

        try {
          $compiled_info['destination'] = $path_from_root;
          $compiled_info['source'] = $source;

          $parser = new scssc();
          $parser->setFormatter('scss_formatter_compressed');
          $compiled_info['file_type'] = 'scss';

          $scss_content = file_get_contents($real_path . DIRECTORY_SEPARATOR . $compiled_info['source']);
          $compiled_info['content'] = $parser->compile($scss_content);
          $files[] = $compiled_info;
        }
        catch (\Exception $exception) {
          $error = $exception->getMessage();
          $this->messenger->addError('CompilerScss error: ' . $error);
        }
      }
    }
    return $files;
  }

  /**
   * Run compiling Less.
   *
   * @param array $config
   *   Parse configuration.
   *
   * @return array
   *   Array with files info.
   */
  private function compileLess(array $config) {
    $files = [];
    $options = [];
    $this->getOptionLess($options);

    // Show error permission on folder.
    $cache_folder = $options['cache_dir'];
    $fdp = $this->fileSystem->realpath($cache_folder);
    if (file_prepare_directory($fdp, FILE_CREATE_DIRECTORY)) {
      if (!is_writable($fdp)) {
        $error = 'Style Management: ' . $this->t('Destination Folder is not writable, please verify permission on') . ' ' . $cache_folder;
        $this->messenger->addError($error);
        return [];
      }
    }
    // Less Parser.
    $parser = new \Less_Parser($options, DRUPAL_ROOT);
    $overrided_config = $this->configFactory->get('style_management.lessfiles');
    foreach ($config as $source => $info) {
      if ((isset($info['watch'])) && ($info['watch'] === TRUE)) {

        // Get Realpath.
        $real_path = $this->fileSystem->realpath($info['destination_path']);

        // Remove subfolder, start at path by DRUPAL_ROOT.
        $path_from_root = str_replace(DRUPAL_ROOT, '', $real_path);

        $path_from_root = substr($path_from_root, 1);

        // Define destination empty.
        try {
          $compiled_info['destination'] = $path_from_root;
          $compiled_info['source'] = $source;
          $compiled_info['file_type'] = 'less';
          $parser->parseFile($source);

          // Cache less.
          $file_id = MainController::makeFileId($compiled_info['source']);
          $modify_vars = $overrided_config->get('setting.alter_variables--' . $file_id);
          $modify_vars_arr = unserialize($modify_vars);

          if (($modify_vars_arr != FALSE) && (count($modify_vars_arr) > 0) && ($modify_vars != 'a:1:{s:0:"";s:0:"";}')) {
            $parser->ModifyVars($modify_vars_arr);
          }
          $compiled_info['content'] = $parser->getCss();
          $files[] = $compiled_info;
        }
        catch (\Exception $exception) {
          $error = $exception->getMessage();
          $this->messenger->addError('CompilerLess error: ' . $error);
        }
      }
    }
    return $files;
    /* @TODO integrate message error \Less is present
     * }
     * else {
     *   $this->messenger->addError('Less Compiler Class not present
     *   return [];
     * }
     */
  }

  /**
   * Get all option for Less Compiler.
   *
   * @param array $options
   *   Parse options.
   */
  private function getOptionLess(array &$options = []) {
    $config = $this->configFactory->get('style_management.settings');

    // Get Realpath.
    $uri_cache_folder = $config->get('setting.less_cache_folder');
    $real_path = $this->fileSystem->realpath($uri_cache_folder);

    // Remove subfolder, start at path by DRUPAL_ROOT.
    $cache_folder = str_replace(DRUPAL_ROOT, '', $real_path);

    $cache_folder = substr($cache_folder, 1);
    $options = [
      'compress' => $config->get('setting.less_compress'),
      'cache_dir' => $cache_folder,
    ];
  }

  /**
   * Get all option for Scss Compiler.
   *
   * @param array $options
   *   Parse options.
   */
  private function getOptionsScss(array &$options = []) {
    $config = $this->configFactory->get('style_management.settings');

    // Get Realpath.
    $uri_cache_folder = $config->get('setting.scss_cache_folder');
    $real_path = $this->fileSystem->realpath($uri_cache_folder);

    // Remove subfolder, start at path by DRUPAL_ROOT.
    $cache_folder = str_replace(DRUPAL_ROOT, '', $real_path);

    $cache_folder = substr($cache_folder, 1);
    $options = [
      'compress' => $config->get('setting.scss_compress'),
      'cache_dir' => $cache_folder,
      'default_dir' => $config->get('setting.scss_default_destination_folder'),
    ];
  }

  /**
   * Get all variables present on file, by path name.
   *
   * @param string $path
   *   Parse String with path File.
   *
   * @return array
   *   Return all variables from file.
   */
  public function getVariablesLessFromPath($path) {
    $variables = [];
    try {
      $parser = new \Less_Parser();
      $parser->parseFile($path);
      $variables = $parser->getVariables();
    }
    catch (\Exception $exception) {
      $error = $exception->getMessage();
      $this->messenger->addError('getVariablesLess error: ' . $error);
    }
    return $variables;
  }

}
