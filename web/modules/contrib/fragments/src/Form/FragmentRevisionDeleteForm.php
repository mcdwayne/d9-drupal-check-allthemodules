<?php

namespace Drupal\fragments\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a fragment revision.
 *
 * @ingroup fragments
 */
class FragmentRevisionDeleteForm extends ConfirmFormBase {

  /**
   * The fragment revision.
   *
   * @var \Drupal\fragments\Entity\FragmentInterface
   */
  protected $revision;

  /**
   * The fragment storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $FragmentStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Dater formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new FragmentRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   Date formatter service.
   */
  public function __construct(EntityStorageInterface $entity_storage, Connection $connection, DateFormatterInterface $dateFormatter) {
    $this->FragmentStorage = $entity_storage;
    $this->connection = $connection;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityManager */
    $entityTypeManager = $container->get('entity_type.manager');
    /** @var \Drupal\Core\Database\Connection $connection */
    $connection = $container->get('database');
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter */
    $dateFormatter = $container->get('date.formatter');

    return new static(
      $entityTypeManager->getStorage('fragment'),
      $connection,
      $dateFormatter
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fragment_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t(
      'Are you sure you want to delete the revision from %revision-date?',
      [
        '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.fragment.version_history', ['fragment' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $fragment_revision = NULL) {
    $this->revision = $this->FragmentStorage->loadRevision($fragment_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->FragmentStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('Fragment: deleted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);

    $replacements = [
      '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
      '%title' => $this->revision->label(),
    ];
    $message = $this->t('Revision from %revision-date of fragment %title has been deleted.', $replacements);
    $this->messenger()->addMessage($message);
    $form_state->setRedirect(
      'entity.fragment.canonical',
       ['fragment' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {fragment_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.fragment.version_history',
         ['fragment' => $this->revision->id()]
      );
    }
  }

}
