<?php

namespace Drupal\config_entity_revisions;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides a form for reverting / publishing a revision.
 */
class ConfigEntityRevisionsRevertFormBase extends ConfirmFormBase {

  /**
   * The entity type manager.
   *
   * @var EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The time service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The content entity revision.
   *
   * @var ConfigEntityRevisionsEntityInterface
   */
  protected $revision;

  /**
   * The config entity.
   *
   * @var ConfigEntityInterface
   */
  protected $config_entity;

  /**
   * The revision ID to be reverted.
   *
   * @var int
   */
  protected $revision_id;

  /**
   * The action (publish or revert).
   *
   * @var string
   */
  protected $action;

  /**
   * The array of configuration strings.
   *
   * @var array
   */
  protected $config;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(EntityTypeManager $entity_type_manager, DateFormatterInterface $date_formatter, TimeInterface $time, MessengerInterface $messenger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
    $this->time = $time;
    $this->messenger = $messenger;

    $match = \Drupal::service('router')->matchRequest(\Drupal::request());
    $this->config_entity = $match['config_entity']->revisioned_entity();
    $this->revision_id = $match['revision_id'];

    $this->revision = $this->config_entity->contentEntityStorage()
      ->loadRevision($this->revision_id);

    $revisionsID = $this->config_entity->getContentEntityID();
    $latest_published = $this->config_entity->contentEntityStorage()
      ->getLatestPublishedRevision($revisionsID);

    $publish = is_null($latest_published) || ($this->revision->getRevisionId() > $latest_published->getRevisionId());
    $this->action = $publish ? 'publish' : 'revert';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('datetime.time'),
      $container->get('messenger')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->config_entity->module_name() . '_revision_revert_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to %action to the revision from %revision-date?', [
      '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
      '%action' => $this->action,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.' . $this->config_entity->getEntityTypeId() . '.revisions', [
      $this->config_entity->getEntityTypeId() => $this->config_entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t(ucfirst($this->action));
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * Prepares a revision to be reverted.
   *
   * @param ConfigEntityRevisionsEntityInterface $revision
   *   The revision to be reverted.
   *
   * @return ConfigEntityRevisionsEntityInterface
   *   The prepared revision ready to be stored.
   */
  public function prepareRevertedRevision(ConfigEntityRevisionsEntityInterface $revision) {
    $revision->setNewRevision();
    $revision->isDefaultRevision(TRUE);

    // The revision timestamp will be updated when the revision is saved. Keep
    // the original one for the confirmation message.
    $original_revision_timestamp = $revision->getRevisionCreationTime();

    $originalLogMessage = $revision->getRevisionLogMessage();
    $logMessage = $originalLogMessage ?
      $this->t('Copy of the revision from %date (%message).', [
        '%date' => $this->dateFormatter->format($original_revision_timestamp),
        '%message' => $originalLogMessage,
      ]) :
      $this->t('Copy of the revision from %date.', [
        '%date' => $this->dateFormatter->format($original_revision_timestamp),
      ]);

    $revision->setRevisionLogMessage($logMessage);
    $revision->setRevisionUserId($this->currentUser()->id());
    $revision->setRevisionCreationTime($this->time->getRequestTime());
    $revision->setChangedTime($this->time->getRequestTime());
    $revision->set('moderation_state', 'draft');
    $revision->setUnpublished();

    return $revision;
  }

  /**
   * Modify the revision's fields so that it becomes published.
   *
   * @param ConfigEntityRevisionsEntityInterface $revision
   *   The revision to be published.
   *
   * @return ConfigEntityRevisionsEntityInterface
   *   The resulting revision record, ready to be saved.
   */
  public function prepareToPublishCurrentRevision(ConfigEntityRevisionsEntityInterface $revision) {
    $revision->set('moderation_state', 'published');
    $revision->setPublished();
    $revision->isDefaultRevision(TRUE);

    return $revision;
  }

  /**
   * Apply the revision insert/update.
   */
  public function applyRevisionChange() {
    if ($this->action == 'revert') {
      $this->revision = $this->prepareRevertedRevision($this->revision);
    }
    else {
      $this->revision = $this->prepareToPublishCurrentRevision($this->revision);
    }
    $this->revision->save();
  }

  /**
   * Update config entity.
   */
  public function updateConfigEntity() {
    $this->config_entity = \Drupal::getContainer()
      ->get('serializer')
      ->deserialize(
        $this->revision->get('configuration')->value,
        get_class($this->config_entity),
        'json');

    $this->config_entity->enforceIsNew(FALSE);
    $this->config_entity->set('settingsOriginal', $this->config_entity->get('settings'));
    $this->config_entity->set('revision_id', $this->revision->getRevisionId());

    $this->config_entity->save();
  }

  /**
   * Log the update.
   */
  public function logUpdate() {
    $this->logger('content')
      ->notice('@form: set @form to revision %revision.', [
        '@form' => $this->config_entity->label(),
        '%revision' => $this->revision->getRevisionId(),
      ]);
  }

  /**
   * Add a message to the page loaded next.
   */
  public function displayUpdate() {
    // The revision timestamp will be updated when the revision is saved. Keep
    // the original one for the confirmation message.
    $original_revision_timestamp = $this->revision->getRevisionCreationTime();

    $this->messenger->addMessage($this->t('%entity_title %title has been set to the revision from %revision-date.', [
      '%entity_title' => $this->config_entity->title(),
      '%title' => $this->config_entity->label(),
      '%revision-date' => $this->dateFormatter->format($original_revision_timestamp),
    ]));
  }

  /**
   * Redirect the user back to the revisions overview page.
   *
   * @param FormStateInterface $form_state
   *   The form state to be modified.
   */
  public function setRedirect(FormStateInterface $form_state) {
    $form_state->setRedirect(
      'entity.' . $this->config_entity->getEntityTypeId() . '.revisions',
      [$this->config_entity->getEntityTypeId() => $this->config_entity->id()]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->applyRevisionChange();
    $this->updateConfigEntity();
    $this->logUpdate();
    $this->displayUpdate();
    $this->setRedirect($form_state);
  }

}
