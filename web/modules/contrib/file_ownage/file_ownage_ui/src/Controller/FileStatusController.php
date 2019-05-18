<?php

namespace Drupal\file_ownage_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\File\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file_ownage\FindManager;

/**
 * Defines a controller to show/check status of a single file.
 *
 * Works on non-entity files, just files_managed items.
 *
 * This will be found by visiting '/admin/content/files/status/{file}'
 */
class FileStatusController extends ControllerBase {

  /**
   * @var  \Drupal\file_ownage\FindManager*/
  public $finder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_ownage.find_manager')
    );
  }

  /**
   * @inheritdoc
   *
   * @param \Drupal\file_ownage\FindManager $finder
   *   The file finder.
   */
  public function __construct(FindManager $finder) {
    $this->finder = $finder;
  }

  /**
   * Check the file status and show what we want to do to it.
   *
   * {@inheritdoc}
   */
  public function view(File $file) {
    $build = [
      '#markup' => 'Hello',
    ];

    $uri = $file->getFileUri();
    $actual_status = $this->finder->pathStatus($uri);

    $strings = [
      '%fid' => $file->id(),
      '%uri' => $uri,
    ];

    if ($actual_status) {
      $build = [
        '#markup' => t('File %fid %uri was found successfully. All is well.', $strings),
      ];
      return $build;
    }

    // Failed, but can try harder.
    $original_uri = $uri;
    $strings['%original_uri'] = $original_uri;

    $updated_status = $this->finder->repair($uri);
    // If changes were made, it's possible the $uri was updated by reference.
    if ($updated_status) {
      $build[] = [
        '#markup' => t('File %original_uri copied to where it should have been. ', $strings),
      ];
    }
    else {
      $build[] = [
        '#markup' => t('Failed to copy file %original_uri to its expected home.', $strings),
      ];

    }

    return $build;
  }

}
