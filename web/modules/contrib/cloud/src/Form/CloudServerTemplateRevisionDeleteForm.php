<?php

namespace Drupal\cloud\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Url;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a Cloud Server Template revision.
 *
 * @ingroup cloud_server_template
 */
class CloudServerTemplateRevisionDeleteForm extends ConfirmFormBase {


  /**
   * The Cloud Server Template revision.
   *
   * @var \Drupal\cloud\Entity\CloudServerTemplateInterface
   */
  protected $revision;

  /**
   * The Cloud Server Template storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $CloudServerTemplateStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Constructs a new CloudServerTemplateRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   */
  public function __construct(EntityStorageInterface $entity_storage,
                              Connection $connection,
                              Messenger $messenger) {
    $this->CloudServerTemplateStorage = $entity_storage;
    $this->connection = $connection;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager->getStorage('cloud_server_template'),
      $container->get('database'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cloud_server_template_revision_delete_confirm';
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
    return new Url('entity.cloud_server_template.version_history', [
      'cloud_context' => $this->revision->getCloudContext(),
      'cloud_server_template' => $this->revision->id(),
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
  public function buildForm(array $form, FormStateInterface $form_state, $cloud_server_template_revision = NULL) {
    $this->revision = $this->CloudServerTemplateStorage->loadRevision($cloud_server_template_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->CloudServerTemplateStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('Cloud Server Template: deleted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $this->messenger->addMessage(t('Revision from %revision-date of Cloud Server Template %title has been deleted.', ['%revision-date' => format_date($this->revision->getRevisionCreationTime()), '%title' => $this->revision->label()]));
    $form_state->setRedirect(
      'entity.cloud_server_template.canonical',
       ['cloud_server_template' => $this->revision->id(), 'cloud_context' => $this->revision->getCloudContext()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {cloud_server_template_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.cloud_server_template.version_history', [
          'cloud_context' => $this->revision->getCloudContext(),
          'cloud_server_template' => $this->revision->id(),
        ]
      );
    }
  }

}
