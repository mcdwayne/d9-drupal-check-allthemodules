<?php

namespace Drupal\white_label_entity\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a While entity revision.
 *
 * @ingroup while
 */
class WhileEntityRevisionDeleteForm extends ConfirmFormBase {


  /**
   * The While entity revision.
   *
   * @var \Drupal\white_label_entity\Entity\WhileEntityInterface
   */
  protected $revision;

  /**
   * The While entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $whileEntityStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new WhileEntityRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(EntityStorageInterface $entity_storage, Connection $connection) {
    $this->whileEntityStorage = $entity_storage;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager->getStorage('while_entity'),
      $container->get('database'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'while_entity_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the revision from %revision-date?', ['%revision-date' => format_date($this->revision->getRevisionCreationTime())]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.while_entity.version_history', ['while_entity' => $this->revision->id()]);
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
  public function buildForm(array $form, FormStateInterface $form_state, $while_entity_revision = NULL) {
    $this->revision = $this->whileEntityStorage->loadRevision($while_entity_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->whileEntityStorage->deleteRevision($this->revision->getRevisionId());
    $entity_name = $this->whileEntityStorage->getEntityType()->getSingularLabel();

    $this->logger('content')->notice('@entity_name: deleted %title revision %revision.', [
      '%title' => $this->revision->label(),
      '%revision' => $this->revision->getRevisionId(),
      '@entity_name' => $entity_name,
    ]);

    drupal_set_message(t('Revision from %revision-date of @entity_name %title has been deleted.', [
      '%revision-date' => format_date($this->revision->getRevisionCreationTime()),
      '%title' => $this->revision->label(),
      '@entity_name' => $entity_name,
    ]));

    $form_state->setRedirect(
      'entity.while_entity.canonical',
       ['while_entity' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {while_entity_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.while_entity.version_history',
         ['while_entity' => $this->revision->id()]
      );
    }
  }

}
