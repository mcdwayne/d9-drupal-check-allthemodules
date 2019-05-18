<?php

namespace Drupal\entity_gallery\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting an entity gallery revision.
 */
class EntityGalleryRevisionDeleteForm extends ConfirmFormBase {

  /**
   * The entity gallery revision.
   *
   * @var \Drupal\entity_gallery\EntityGalleryInterface
   */
  protected $revision;

  /**
   * The entity gallery storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityGalleryStorage;

  /**
   * The entity gallery type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityGalleryTypeStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new EntityGalleryRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_gallery_storage
   *   The entity gallery storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_gallery_type_storage
   *   The entity gallery type storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(EntityStorageInterface $entity_gallery_storage, EntityStorageInterface $entity_gallery_type_storage, Connection $connection) {
    $this->entityGalleryStorage = $entity_gallery_storage;
    $this->entityGalleryTypeStorage = $entity_gallery_type_storage;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager->getStorage('entity_gallery'),
      $entity_manager->getStorage('entity_gallery_type'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_gallery_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the revision from %revision-date?', array('%revision-date' => format_date($this->revision->getRevisionCreationTime())));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.entity_gallery.version_history', array('entity_gallery' => $this->revision->id()));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_gallery_revision = NULL) {
    $this->revision = $this->entityGalleryStorage->loadRevision($entity_gallery_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entityGalleryStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('@type: deleted %title revision %revision.', array('@type' => $this->revision->bundle(), '%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()));
    $entity_gallery_type = $this->entityGalleryTypeStorage->load($this->revision->bundle())->label();
    drupal_set_message(t('Revision from %revision-date of @type %title has been deleted.', array('%revision-date' => format_date($this->revision->getRevisionCreationTime()), '@type' => $entity_gallery_type, '%title' => $this->revision->label())));
    $form_state->setRedirect(
      'entity.entity_gallery.canonical',
      array('entity_gallery' => $this->revision->id())
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {entity_gallery_field_revision} WHERE egid = :egid', array(':egid' => $this->revision->id()))->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.entity_gallery.version_history',
        array('entity_gallery' => $this->revision->id())
      );
    }
  }

}
