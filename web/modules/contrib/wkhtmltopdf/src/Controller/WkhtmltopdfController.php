<?php

namespace Drupal\wkhtmltopdf\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\File\FileSystem;

/**
 * Base for Controller.
 */
class WkhtmltopdfController extends ControllerBase {

  public $settings;

  public $request;

  public $filesystem;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactory $config, Request $request, FileSystem $filesystem) {
    $this->settings = $config->get('wkhtmltopdf.settings');
    $this->request = $request;
    $this->filesystem = $filesystem;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('file_system')
    );
  }

  /**
   * Generate pdf file.
   *
   * @return Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect
   */
  public function generatePdf() {
    $url = $this->request->query->get('url');

    $binary = $this->settings->get('wkhtmltopdf_bin');
    $path = $this->filesystem->realpath('public://wkhtmltopdf');
    $parameters = '--javascript-delay 5000';
    $parameters .= $this->settings->get('wkhtmltopdf_zoom') ? ' --zoom ' . $this->settings->get('wkhtmltopdf_zoom') : '';

    $file_path = "public://wkhtmltopdf/" . urlencode($url) . '.pdf';
    if (!file_prepare_directory($file_path)) {
      drupal_mkdir($path);
    }

    $filename = urlencode($url) . '.pdf';
    $file_path_physical = $path . '/' . $filename;
    $command = $binary . ' ' . $parameters . ' ' . $url . ' ' . $file_path_physical;

    $lock = \Drupal::lock();
    if ($lock->acquire(__FILE__) !== FALSE) {
      shell_exec($command);
      $lock->release(__FILE__);
    }
    else {
      while ($lock->acquire(__FILE__) === FALSE) {
        $lock->wait(__FILE__, 3);
      }
      if ($lock->acquire(__FILE__) !== FALSE) {
        shell_exec($command);
        $lock->release(__FILE__);
      }
    }

    $force_download = $this->settings->get('wkhtmltopdf_download');

    if ($force_download) {
      $this->download($file_path_physical, $filename);
      return new RedirectResponse($url);
    }

    $url_redirect = file_create_url($file_path);
    return new RedirectResponse($url_redirect);
  }

  /**
   * Force download and now redirect.
   *
   * @param string $file_path_physical
   *   File path.
   * @param string $filename
   *   File name.
   */
  public function download($file_path_physical, $filename) {
    header("Content-type: octet/stream");
    header("Content-disposition: attachment; filename=" . $filename . ";");
    header("Content-Length: " . filesize($file_path_physical));
    readfile($file_path_physical);
    exit;
  }

}
