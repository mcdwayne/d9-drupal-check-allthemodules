<?php
/**
 * @file
 * Contains \Drupal\docbinder\Controller\DocbinderController.
 */

namespace Drupal\docbinder\Controller;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\docbinder\Ajax\RemoveFileCommand;
use Drupal\docbinder\Ajax\UpdateFileCountCommand;
use Drupal\file\Entity\File;


class DocBinderController extends ControllerBase {

  /**
   * The tempstore.
   *
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $tempStore;

  /**
   * Constructs a new DocBinder controller.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    $this->tempStore = $temp_store_factory->get('docbinder');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private')
    );
  }

  public function pageTitle() {
    $config = $this->config('docbinder.settings');
    return "Download " . $config->get('collection.name');
  }

  public function addFile(File $file) {
    $response = new AjaxResponse();

    $files = $this->tempStore->get('files');
    if (array_key_exists($file->id(), $files)) {
      // File already in list
      $this->tempStore->set('statusCode', 304);
      $response->addCommand(new UpdateFileCountCommand($this->tempStore));
    }
    else {
      $files[$file->id()] = $file;
      $this->tempStore->set('files', $files);
      $this->tempStore->set('addedLast', $file->id());
      $this->tempStore->set('statusCode', 200);
      $response->addCommand(new UpdateFileCountCommand($this->tempStore));
    }

    return $response;
  }

  public function removeFile(File $file) {
    $response = new AjaxResponse();

    $files = $this->tempStore->get('files');
    if (array_key_exists($file->id(), $files)) {
      // File is in list
      unset($files[$file->id()]);
      $this->tempStore->set('files', $files);
      $this->tempStore->set('removedLast', $file->id());
      $this->tempStore->set('statusCode', 200);
      $response->addCommand(new RemoveFileCommand($this->tempStore));
      $response->addCommand(new UpdateFileCountCommand($this->tempStore));
    }
    else {
      $this->tempStore->set('statusCode', 404);
      $response->addCommand(new UpdateFileCountCommand($this->tempStore));
    }

    return $response;
  }
}
