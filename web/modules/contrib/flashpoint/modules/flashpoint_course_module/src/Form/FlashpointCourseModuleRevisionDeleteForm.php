<?php

namespace Drupal\flashpoint_course_module\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a Course module revision.
 *
 * @ingroup flashpoint
 */
class FlashpointCourseModuleRevisionDeleteForm extends ConfirmFormBase {


  /**
   * The Course module revision.
   *
   * @var \Drupal\flashpoint_course_module\Entity\FlashpointCourseModuleInterface
   */
  protected $revision;

  /**
   * The Course module storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $FlashpointCourseModuleStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new FlashpointCourseModuleRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(EntityStorageInterface $entity_storage, Connection $connection) {
    $this->FlashpointCourseModuleStorage = $entity_storage;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager->getStorage('flashpoint_course_module'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flashpoint_course_module_revision_delete_confirm';
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
    return new Url('entity.flashpoint_course_module.version_history', ['flashpoint_course_module' => $this->revision->id()]);
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
  public function buildForm(array $form, FormStateInterface $form_state, $flashpoint_course_module_revision = NULL) {
    $this->revision = $this->FlashpointCourseModuleStorage->loadRevision($flashpoint_course_module_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->FlashpointCourseModuleStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('Course module: deleted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    drupal_set_message(t('Revision from %revision-date of Course module %title has been deleted.', ['%revision-date' => format_date($this->revision->getRevisionCreationTime()), '%title' => $this->revision->label()]));
    $form_state->setRedirect(
      'entity.flashpoint_course_module.canonical',
       ['flashpoint_course_module' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {flashpoint_course_module_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.flashpoint_course_module.version_history',
         ['flashpoint_course_module' => $this->revision->id()]
      );
    }
  }

}
