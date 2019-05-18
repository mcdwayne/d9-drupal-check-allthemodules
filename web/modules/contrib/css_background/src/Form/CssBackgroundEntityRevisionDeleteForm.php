<?php

namespace Drupal\css_background\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a CssBackground revision.
 *
 * @ingroup css_background
 */
class CssBackgroundEntityRevisionDeleteForm extends ConfirmFormBase {

  /**
   * The CssBackground revision.
   *
   * @var \Drupal\css_background\Entity\CssBackgroundEntityInterface
   */
  protected $revision;

  /**
   * The CssBackground storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $CssBackgroundEntityStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new CssBackgroundEntityRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(EntityStorageInterface $entity_storage, Connection $connection) {
    $this->CssBackgroundEntityStorage = $entity_storage;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager->getStorage('css_background'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'css_background_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the revision from %revision-date?', [
      '%revision-date' => format_date($this->revision->getRevisionCreationTime()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.css_background.version_history', ['css_background' => $this->revision->id()]);
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
  public function buildForm(array $form, FormStateInterface $form_state, $css_background_revision = NULL) {
    $this->revision = $this->CssBackgroundEntityStorage->loadRevision($css_background_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->CssBackgroundEntityStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('CssBackground: deleted %title revision %revision.', [
      '%title' => $this->revision->label(),
      '%revision' => $this->revision->getRevisionId(),
    ]);
    drupal_set_message($this->t('Revision from %revision-date of CssBackground %title has been deleted.', [
      '%revision-date' => format_date($this->revision->getRevisionCreationTime()),
      '%title' => $this->revision->label(),
    ]));
    $form_state->setRedirect('entity.css_background.canonical', ['css_background' => $this->revision->id()]);
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {css_background_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect('entity.css_background.version_history', [
        'css_background' => $this->revision->id(),
      ]);
    }
  }

}
