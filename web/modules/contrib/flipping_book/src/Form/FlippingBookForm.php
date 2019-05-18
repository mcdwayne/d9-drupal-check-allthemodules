<?php

namespace Drupal\flipping_book\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flipping_book\Entity\FlippingBook;
use Drupal\flipping_book\Entity\FlippingBookType;
use Drupal\flipping_book\FlippingBookInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Flipping Book edit forms.
 *
 * @ingroup flipping_book
 */
class FlippingBookForm extends ContentEntityForm {

  /**
   * File System service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Archiver Manager service.
   *
   * @var \Drupal\Core\Archiver\ArchiverManager
   */
  protected $archiverManager;

  /**
   * Flipping Book service.
   *
   * @var \Drupal\flipping_book\FlippingBookInterface
   */
  protected $flippingBook;

  /**
   * Flipping book location.
   *
   * @var string
   */
  protected $location;

  /**
   * Flipping book destination.
   *
   * @var string
   */
  protected $destination;

  /**
   * Flipping book filepath.
   *
   * @var string
   */
  protected $filepath;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityManagerInterface $entity_manager, FileSystemInterface $file_system, PluginManagerInterface $pluginManager, FlippingBookInterface $flipping_book) {
    parent::__construct($entity_manager);
    $this->fileSystem = $file_system;
    $this->archiverManager = $pluginManager;
    $this->flippingBook = $flipping_book;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('file_system'),
      $container->get('plugin.manager.archiver'),
      $container->get('flipping_book')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['file']['widget'][0]['#upload_location'] = $this->getUploadLocation();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->handleArchive($form_state);

    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Flipping Book.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Flipping Book.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.flipping_book.canonical', ['flipping_book' => $entity->id()]);
  }

  /**
   * Get upload location.
   *
   * @return string
   *   The Flipping Book upload location.
   */
  public function getUploadLocation() {
    if (!isset($this->location)) {
      $this->setUploadLocation();
    }

    return $this->location;
  }

  /**
   * Extract upload location.
   *
   * @param \Drupal\flipping_book\Entity\FlippingBook $flippingBook
   *   A Flipping Book entity.
   *
   * @return string
   *   The Flipping Book upload location.
   */
  protected function extractUploadLocation(FlippingBook $flippingBook) {
    $flipping_book_type = FlippingBookType::load($flippingBook->bundle());
    return $flipping_book_type->get('location');
  }

  /**
   * Set upload location.
   */
  protected function setUploadLocation() {
    $this->location = $this->flippingBook->extractUploadLocation($this->entity);
  }

  /**
   * Get file field access.
   *
   * @return bool
   *   Whether or not file field must be shown.
   */
  protected function getFileFieldAccess() {
    if ($this->entity->get('directory')->value) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Handle uploaded archive.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state instance.
   */
  protected function handleArchive(FormStateInterface $form_state) {
    $file = $form_state->getValue('file');

    if (!empty($file[0]['fids'])) {
      $this->prepareExport($form_state);

      try {
        $this->flippingBook->extractArchive($this->filepath, $this->destination);
      }
      catch (\Exception $e) {
        $message = $this->t('Cannot extract Flipping Book file: @message', [
          '@message' => $e->getMessage(),
        ]);
        drupal_set_message($message, 'error');
        return;
      }

      $this->entity->set('directory', str_replace($this->getUploadLocation() . '/', '', $this->destination));
      return;
    }

    $storage = $this->entityTypeManager->getStorage('flipping_book');
    $orig = $storage->load($this->entity->id());

    if (!empty($orig->get('file')->getValue()) && empty($this->entity->get('file')->getValue())) {
      $this->deleteFlippingBook($orig);
    }
  }

  /**
   * Delete Flipping Book.
   *
   * @param \Drupal\flipping_book\Entity\FlippingBook $flippingBook
   *   A Flipping Book entity.
   */
  protected function deleteFlippingBook(FlippingBook $flippingBook) {
    $this->flippingBook->deleteArchive($flippingBook);

    $data = $flippingBook->get('file')->getValue();
    $file = $this->getFileStorage()->load($data[0]['target_id']);
    $file->delete();
  }

  /**
   * Prepare export.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state instance.
   */
  protected function prepareExport(FormStateInterface $form_state) {
    $file = reset($form_state->getValue('file'));
    $file = $this->getFileStorage()->load($file['fids'][0]);
    $file->setPermanent();
    $file->save();

    $info = $this->flippingBook->prepareExportDirectory($file, $this->getUploadLocation());
    $this->filepath = $info['filepath'];
    $this->destination = $info['destination'];
  }

  /**
   * Get File storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   A file storage instance.
   */
  protected function getFileStorage() {
    return $this->entityTypeManager->getStorage('file');
  }

}
