<?php

namespace Drupal\simple_megamenu\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a Simple mega menu revision.
 *
 * @ingroup simple_megamenu
 */
class SimpleMegaMenuRevisionDeleteForm extends ConfirmFormBase {


  /**
   * The Simple mega menu revision.
   *
   * @var \Drupal\simple_megamenu\Entity\SimpleMegaMenuInterface
   */
  protected $revision;

  /**
   * The Simple mega menu storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $SimpleMegaMenuStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new SimpleMegaMenuRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(EntityStorageInterface $entity_storage, Connection $connection) {
    $this->SimpleMegaMenuStorage = $entity_storage;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager->getStorage('simple_mega_menu'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_mega_menu_revision_delete_confirm';
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
    return new Url('entity.simple_mega_menu.version_history', ['simple_mega_menu' => $this->revision->id()]);
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
  public function buildForm(array $form, FormStateInterface $form_state, $simple_mega_menu_revision = NULL) {
    $this->revision = $this->SimpleMegaMenuStorage->loadRevision($simple_mega_menu_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->SimpleMegaMenuStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('Simple mega menu: deleted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    drupal_set_message(t('Revision from %revision-date of Simple mega menu %title has been deleted.', ['%revision-date' => format_date($this->revision->getRevisionCreationTime()), '%title' => $this->revision->label()]));
    $form_state->setRedirect(
      'entity.simple_mega_menu.canonical',
       ['simple_mega_menu' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {simple_mega_menu_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.simple_mega_menu.version_history',
         ['simple_mega_menu' => $this->revision->id()]
      );
    }
  }

}
