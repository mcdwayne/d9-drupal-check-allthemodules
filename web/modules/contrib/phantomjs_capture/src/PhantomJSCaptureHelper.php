<?php

namespace Drupal\phantomjs_capture;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Drupal\phantomjs_capture\PhantomJSCaptureHelperInterface;
use Drupal\Core\Url;

class PhantomJSCaptureHelper implements PhantomJSCaptureHelperInterface {

  /**
   * @var ConfigFactoryInterface;
   */
  private $configFactory;

  /**
   * @var LoggerChannelFactoryInterface
   */
  private $loggerFactory;

  /**
   * @var FileSystemInterface
   */
  private $fileSystem;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * PhantomJSCaptureHelper constructor.
   * @param ConfigFactoryInterface $config_factory
   * @param FileSystemInterface $file_system
   * @param LoggerChannelFactoryInterface $logger
   */
  public function __construct(ConfigFactoryInterface $config_factory, FileSystemInterface $file_system, LoggerChannelFactoryInterface $logger) {
    $this->configFactory = $config_factory;
    $this->fileSystem = $file_system;
    $this->loggerFactory = $logger;
    $this->config = $this->configFactory->get('phantomjs_capture.settings');
  }

  /**
   * Check that the binary exists at the path that was given.
   * @param $path
   * @return bool
   */
  public function binaryExists($path) {
    if (is_null($path) || !file_exists($path)) {
      throw new FileNotFoundException($path);
    }

    return TRUE;
  }

  /**
   * Return the version of PhantomJS binary on the server.
   * @return mixed
   */
  public function getVersion() {
    $binary = $this->config->get('binary');

    if ($this->binaryExists($binary)) {
      $output = [];
      exec($binary . ' -v', $output);
      return $output[0];
    }

    return FALSE;
  }

  /**
   * Captures a screen shot using PhantomJS by calling the program.
   *
   * @param Url $url
   *   An instance of Drupal\Core\Url, the address you want to capture.
   * @param string $destination
   *   The destination for the file (e.g. public://screenshot).
   * @param string $filename
   *   The filename to store the file as.
   * @param string $element
   *   The id of the DOM element to render in the document.
   *
   * @return bool
   *   Returns TRUE if the screen shot was taken else FALSE on error.
   */
  public function capture(Url $url, $destination, $filename, $element = NULL) {
    $binary = $this->config->get('binary');
    $script = $this->fileSystem->realpath($this->config->get('script'));

    if (!$this->binaryExists($binary)) {
      throw new FileNotFoundException($binary);
    }

    // Check that destination is writable.
    // @todo: would be nice to throw an exception instead of return FALSE
    if (!file_prepare_directory($destination, FILE_CREATE_DIRECTORY)) {
      $this->loggerFactory->get('phantomjs_capture')->error('The directory %directory for the file %filename could not be created or is not accessible.', ['%directory' => $destination, '%filename' => $filename]);
      return FALSE;
    }

    $url = $url->toUriString();
    $destination = $this->fileSystem->realpath($destination . '/' . $filename);

    $output = [];

    if ($element) {
      exec($binary . ' ' . $script . ' "' . $url . '" ' . $destination . ' ' . escapeshellarg($element), $output);
    }
    else {
      exec($binary . ' ' . $script . ' "' . $url . '" ' . $destination, $output);
    }

    // Check that PhantomJS was able to load the page.
    if ($output[0] == '500') {
      $this->loggerFactory->get('phantomjs_capture')->error('PhantomJS could not capture the URL %url.', ['%url' => $url]);
      return FALSE;
    }

    return TRUE;
  }
}