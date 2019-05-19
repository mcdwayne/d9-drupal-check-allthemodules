<?php

namespace Drupal\trance\Form;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\trance\TranceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting a trance revision.
 */
class TranceRevisionRevertForm extends ConfirmFormBase {

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
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a new TranceRevisionRevertForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $trance_storage
   *   The trance storage.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityStorageInterface $trance_storage, DateFormatter $date_formatter) {
    $this->tranceStorage = $trance_storage;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('trance'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    $entity_type = $this->tranceStorage->getEntityType()->id();
    return $entity_type . '_revision_revert_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to revert to the revision from %revision-date?', ['%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime())]);
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
    return t('Revert');
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
  public function buildForm(array $form, FormStateInterface $form_state, $trance_revision = NULL) {
    $this->revision = $this->tranceStorage->loadRevision($trance_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The revision timestamp will be updated when the revision is saved. Keep
    // the original one for the confirmation message.
    $original_revision_timestamp = $this->revision->getRevisionCreationTime();

    $entity_type = $this->revision->getEntityType()->id();
    $trance_bundle = $this->revision->bundle();

    $this->revision = $this->prepareRevertedRevision($this->revision, $form_state);
    $this->revision->revision_log = t('Copy of the revision from %date.', ['%date' => $this->dateFormatter->format($original_revision_timestamp)]);
    $this->revision->save();

    $this->logger('content')->notice('@type @bundle: reverted %title revision %revision.', [
      '@type' => $entity_type,
      '@bundle' => $trance_bundle,
      '%title' => $this->revision->label(),
      '%revision' => $this->revision->getRevisionId(),
    ]);
    drupal_set_message(t('@type @bundle %title has been reverted to the revision from %revision-date.', [
      '@type' => $entity_type,
      '@bundle' => $trance_bundle,
      '%title' => $this->revision->label(),
      '%revision-date' => $this->dateFormatter->format($original_revision_timestamp),
    ]));
    $form_state->setRedirect('entity.' . $entity_type . '.version_history', [
      $entity_type => $this->revision->id(),
    ]);
  }

  /**
   * Prepares a revision to be reverted.
   *
   * @param \Drupal\trance\TranceInterface $revision
   *   The revision to be reverted.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\trance\TranceInterface
   *   The prepared revision ready to be stored.
   */
  protected function prepareRevertedRevision(TranceInterface $revision, FormStateInterface $form_state) {
    $revision->setNewRevision();
    $revision->isDefaultRevision(TRUE);

    return $revision;
  }

}
