<?php

namespace Drupal\images_optimizer\HookHandler;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\file\FileInterface;
use Drupal\images_optimizer\Helper\FileHelper;
use Drupal\images_optimizer\Helper\OptimizerHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Hook handler for the file_insert() hook.
 *
 * @package Drupal\images_optimizer\Hook
 */
class FileInsertHookHandler implements ContainerInjectionInterface {

  /**
   * The optimizer helper.
   *
   * @var \Drupal\images_optimizer\Helper\OptimizerHelper
   */
  private $optimizerHelper;

  /**
   * The file helper.
   *
   * @var \Drupal\images_optimizer\Helper\FileHelper
   */
  private $fileHelper;

  /**
   * FileInsertHookHandler constructor.
   *
   * @param \Drupal\images_optimizer\Helper\OptimizerHelper $optimizer_helper
   *   The optimizer helper.
   * @param \Drupal\images_optimizer\Helper\FileHelper $file_helper
   *   The file helper.
   */
  public function __construct(OptimizerHelper $optimizer_helper, FileHelper $file_helper) {
    $this->optimizerHelper = $optimizer_helper;
    $this->fileHelper = $file_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('images_optimizer.helper.optimizer'),
      $container->get('images_optimizer.helper.file')
    );
  }

  /**
   * Try to optimize the inserted file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file.
   *
   * @return bool
   *   TRUE if the file was optimized, FALSE otherwise.
   */
  public function process(FileInterface $file) {
    if (!empty(file_validate_is_image($file))) {
      return FALSE;
    }

    if (!$this->optimizerHelper->optimize($file->getMimeType(), $file->getFileUri())) {
      return FALSE;
    }

    $this->fileHelper->updateSize($file);

    return TRUE;
  }

}
