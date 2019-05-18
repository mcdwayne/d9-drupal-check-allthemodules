<?php

namespace Drupal\mimedetect;

use Drupal\file\FileInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

/**
 * MimeDetect service.
 */
class MimeDetectService implements MimeDetectServiceInterface {

  /**
   * File system helpers to operate on files.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The MIME type guesser.
   *
   * @var \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface
   */
  protected $mimeTypeGuesser;

  /**
   * The MIME detector plugin manager.
   *
   * @var \Drupal\mimedetect\MimeDetectPluginManager
   */
  protected $mimeDetectorPluginManager;

  /**
   * The PHP fileinfo instance.
   *
   * @var \finfo
   */
  protected $finfo;

  /**
   * Full UNIX 'file' command line.
   *
   * @var string
   */
  protected $unixfilecmd;

  /**
   * Construct the MimeDetectService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   File system helpers to operate on files.
   * @param \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface $mime_type_guesser
   *   The MIME type guesser instance to use.
   * @param \Drupal\mimedetect\MimeDetectPluginManager $mime_detector_plugin
   *   The MIME detect plugin manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FileSystemInterface $file_system, MimeTypeGuesserInterface $mime_type_guesser, MimeDetectPluginManager $mime_detector_plugin) {
    $this->fileSystem = $file_system;
    $this->mimeTypeGuesser = $mime_type_guesser;
    $this->mimeDetectorPluginManager = $mime_detector_plugin;

    $config = $config_factory->get('mimedetect.settings');

    // Enable fileinfo engine.
    if ($config->get('fileinfo.enable') && $this->checkFileinfo($config->get('magicfile'))) {
      $this->finfo = new \finfo(FILEINFO_MIME_TYPE, $config->get('magicfile'));
    }

    // Enable UNIX 'file' command engine.
    if ($config->get('unixfile.enable') && $this->checkUnixfile($config->get('unixfile.binary'), $config->get('magicfile'))) {
      $this->unixfilecmd = $config->get('unixfile.binary') . ' --brief --mime' . (!empty($config->get('magicfile')) ? ' --magic-file=' . escapeshellarg($config->get('magicfile')) : '') . ' ';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMime(FileInterface $file) {
    $file_path = $this->fileSystem->realpath($file->getFileUri());

    // Try any specific MIME detector for the filename extension.
    $mime = $this->getMimeByDetector($file);

    // Try PHP fileinfo.
    if (!$mime && $this->finfo) {
      $mime = $this->finfo->file($file_path);
    }

    // Try the 'file' UNIX command.
    if ((!$mime || $mime == 'application/octet-stream') && $this->unixfilecmd) {
      $mime = trim(exec($this->unixfilecmd . escapeshellarg($file_path)));
    }

    if ($mime) {
      // With text we often get charset like 'text/plain; charset=us-ascii'.
      $mime = explode(';', $mime);
      $mime = trim($mime[0]);
    }
    else {
      // Try file name extension mapping.
      $mime = $this->mimeTypeGuesser->guess($file_path);
    }

    return $mime ?: 'application/octet-stream';
  }

  /**
   * Get the MIME type for a given file by MIME detector plugins.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file to be analyzed.
   *
   * @return string
   *   The detected MIME, NULL if no detectors available for the given filename
   *   or its content is not recognized by any detector.
   */
  protected function getMimeByDetector(FileInterface $file) {
    $filename_parts = explode('.', $file->getFilename());
    $filename_extension = strtolower(array_pop($filename_parts));
    $file_path = $this->fileSystem->realpath($file->getFileUri());

    $mime = NULL;
    foreach ($this->mimeDetectorPluginManager->getDefinitions() as $plugin_id => $definition) {
      if (in_array($filename_extension, $definition['filename_extensions'])
        && ($mime = $this->mimeDetectorPluginManager->createInstance($plugin_id)->detect($file_path))) {
        break;
      }
    }

    return $mime;
  }

  /**
   * Checks a 'magic' file information.
   *
   * @param string $magic_file
   *   Path to magic file.
   * @param string $msg
   *   Optional string variable reference to place error message.
   *
   * @return bool
   *   Test result. TRUE if OK, FALSE on error.
   */
  public static function checkMagicfile($magic_file, &$msg = '') {
    $errors = FALSE;

    // Basic file exists check.
    if ($errors = !file_exists($magic_file)) {
      $msg = t("The path %path does not exist or is not readable by your webserver.", ['%path' => $magic_file]);
    }
    elseif ($errors = (filetype($magic_file) != 'file' || !@filesize($magic_file))) {
      $msg = t("Could not load the magic file %path.", ['%path' => $magic_file]);
    }

    return !$errors;
  }

  /**
   * Checks the availability for PHP fileinfo mime detection engine.
   *
   * @param string $magic_file
   *   An optional 'magic' information file.
   * @param string $msg
   *   Optional string variable reference to place error message.
   *
   * @return bool
   *   Test result. TRUE if OK, FALSE on error.
   */
  public static function checkFileinfo($magic_file = '', &$msg = '') {
    // Test magic file if not empty.
    $errors = !empty($magic_file) ? !MimeDetectService::checkMagicfile($magic_file, $msg) : FALSE;
    if ($errors) {
      return FALSE;
    }

    // Check extension availability.
    if ($errors = !extension_loaded('fileinfo')) {
      $msg = t('PHP file information extension not found.');
    }
    elseif ($errors = !@finfo_open(FILEINFO_MIME, $magic_file)) {
      if (!empty($magic_file)) {
        $msg = t('Fileinfo cannot load the configured magic file %path. It could be corrupted. Try another magic file or remove your magic file path settings to use defaults.', ['%path' => $magic_file]);
      }
      else {
        $msg = t('Fileinfo could not load magic information. Check the MAGIC environment variable on your system and that fileinfo PHP extension is properly installed.');
      }
    }

    return !$errors;
  }

  /**
   * Checks UNIX 'file' command system avilability.
   *
   * @param string $bin_file
   *   Optional file path to the UNIX file command binary.
   * @param string $magic_file
   *   An optional 'magic' information file.
   * @param string $msg
   *   Optional string variable reference to place the UNIX file command version
   *   if available or error message.
   *
   * @return bool
   *   Test result. TRUE if OK, FALSE on error.
   */
  public static function checkUnixfile($bin_file = '/usr/bin/file', $magic_file = '', &$msg = '') {
    // Test magic file if not empty.
    $errors = !empty($magic_file) ? !MimeDetectService::checkMagicfile($magic_file, $msg) : FALSE;
    if ($errors) {
      return FALSE;
    }

    if ($errors = empty($bin_file)) {
      $msg = t("You must specify the path to the 'file' binary if it is enabled.");
    }
    elseif ($errors = basename($bin_file) != 'file') {
      $msg = t("Base name %basename for the 'file' binary not allowed. Must be 'file'.", ['%basename' => basename($bin_file)]);
    }
    elseif ($errors = !file_exists($bin_file)) {
      $msg = t("The path %path does not exist or is not readable by your webserver.", ['%path' => $bin_file]);
    }
    elseif ($errors = !is_executable($bin_file)) {
      $msg = t("%path is not executable by your webserver.", ['%path' => $bin_file]);
    }
    else {
      // Execution test.
      $exit_code = 0;
      $output = NULL;
      exec($bin_file . (!empty($magic_file) ? ' --magic-file=' . escapeshellarg($magic_file) : '') . ' --version', $output, $exit_code);
      if ($errors = $exit_code !== 0) {
        if (!empty($magic_file)) {
          $msg = t('File command execution failed with exit code %code. Could not load the magic file %file.', ['%code' => $exit_code, '%file' => $magic_file]);
        }
        else {
          $msg = t('File command execution failed with exit code %code.', ['%code' => $exit_code]);
        }
      }
      elseif ($errors = (!is_array($output) || !count($output) || strpos($output[0], 'file-') !== 0)) {
        // Expected output: "file-x.xx".
        $msg = t('Unable to determine the UNIX file command version.');
      }
      else {
        $msg = substr($output[0], strlen('file-'));
      }
    }

    return !$errors;
  }

}
