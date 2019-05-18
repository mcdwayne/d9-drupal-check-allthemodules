<?php

namespace Drupal\cloudwords\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\Renderer;
use Symfony\Component\HttpFoundation\Response;
/**
 * Class CloudwordsFileController.
 *
 * @package Drupal\cloudwords\Controller
 */
class CloudwordsFileController extends ControllerBase {

  protected $renderer;
  public function __construct(Renderer $renderer) {
    $this->renderer = $renderer;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }
  /**
   * Overviewpage.
   *
   * @return string
   *   Return Hello string.
   */
  public function download(\Drupal\cloudwords\CloudwordsDrupalProject $cloudwords_project, \Drupal\cloudwords\CloudwordsFile $cloudwords_file) {
    $filename = $cloudwords_file->getFilename();
    $filedata = cloudwords_get_api_client()->download_reference_file($cloudwords_project->getId(), $cloudwords_file->getId());
    header('Content-Disposition: attachment; filename=' . basename($filename));
    header('Content-Type: application/force-download');
    header('Content-Type: application/octet-stream');
    header('Content-Type: application/download');
    header('Content-Description: File Transfer');
    header('Content-Length: ' . strlen($filedata));
    echo $filedata;
    die();
  }
}
