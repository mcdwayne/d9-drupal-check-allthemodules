<?php

namespace Drupal\trance\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting a trance revision.
 */
class TranceRevisionDeleteForm extends ConfirmFormBase {

  /**
   * The trance revision.
   *
   * @var \Drupal\trance\TranceInterface
   */
  protected $revision;

  /**
   * The trance storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $tranceStorage;

  /**
   * The trance type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $tranceTypeStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new TranceRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $trance_storage
   *   The trance storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $trance_type_storage
   *   The trance type storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(EntityStorageInterface $trance_storage, EntityStorageInterface $trance_type_storage, Connection $connection) {
    $this->tranceStorage = $trance_storage;
    $this->tranceTypeStorage = $trance_type_storage;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager->getStorage('trance'),
      $entity_manager->getStorage('trance_type'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    $entity_type = $this->tranceStorage->getEntityType()->id();
    return $entity_type . '_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the revision from %revision-date?', [
      '%revision-date' => format_date($this->revision->getRevisionCreationTime()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $entity_type = $this->tranceStorage->getEntityType()->id();
    return new Url('entity.' . $entity_type . '.version_history', [
      $entity_type => $this->revision->id(),
    ]);
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
  public function buildForm(array $form, FormStateInterface $form_state, $trance_revision = NULL) {
    $this->revision = $this->tranceStorage->loadRevision($trance_revision);
    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->tranceStorage->deleteRevision($this->revision->getRevisionId());

    $entity_type = $this->revision->getEntityType()->id();
    $trance_bundle = $this->revision->bundle();
    $this->tranceTypeStorage->load($this->revision->bundle())->label();

    $this->logger('trance')->notice('@type: deleted %title revision %revision.', [
      '@type' => $trance_bundle,
      '%title' => $this->revision->label(),
      '%revision' => $this->revision->getRevisionId(),
    ]);
    drupal_set_message(t('Revision from %revision-date of @type @bundle %title has been deleted.', [
      '%revision-date' => format_date($this->revision->getRevisionCreationTime()),
      '@type' => $entity_type,
      '@bundle' => $trance_bundle,
      '%title' => $this->revision->label(),
    ]));
    $form_state->setRedirect(
      'entity.' . $entity_type . '.canonical', [
        $entity_type => $this->revision->id(),
      ]);
    if ($this->connection->query('SELECT COUNT(DISTINCT revision_id) FROM {' . $entity_type . '_field_revision} WHERE id = :id', [
      ':id' => $this->revision->id(),
    ])->fetchField() > 1) {
      $form_state->setRedirect('entity.' . $entity_type . '.version_history', [
        $entity_type => $this->revision->id(),
      ]);
    }
  }

}
