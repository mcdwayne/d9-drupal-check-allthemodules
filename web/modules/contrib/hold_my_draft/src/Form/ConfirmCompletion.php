<?php

namespace Drupal\hold_my_draft\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hold_my_draft\Utilities;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for completing a draft-hold.
 */
class ConfirmCompletion extends ConfirmFormBase {

  /**
   * The node revision.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $revision;

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

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
   * The revision utilities service.
   *
   * @var \Drupal\hold_my_draft\Utilities
   */
  protected $utilities;

  /**
   * Constructs a new ConfirmCompletion form.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_storage
   *   The node storage.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\hold_my_draft\Utilities $utilities
   *   The draft-hold utilities service.
   */
  public function __construct(EntityStorageInterface $node_storage, DateFormatterInterface $date_formatter, TimeInterface $time, Utilities $utilities) {
    $this->nodeStorage = $node_storage;
    $this->dateFormatter = $date_formatter;
    $this->time = $time;
    $this->utilities = $utilities;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('node'),
      $container->get('date.formatter'),
      $container->get('datetime.time'),
      $container->get('hold_my_draft.utilities')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_hold_my_draft_complete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to complete the draft-hold?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.node.edit_form',
      ['node' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Restore held draft');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('The unpublished revision from %revision-date will be restored. 
    This wast the latest revision at the beginning of the draft-hold process. 
    Saved revisions will not be changed or removed.',
      [
        '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node_revision = NULL) {
    $this->revision = $this->nodeStorage->loadRevision($node_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // End tracking the draft-hold.
    $this->utilities->endDraftHold($this->revision, TRUE);

    // The revision timestamp will be updated when the revision is saved. Keep
    // the original one for the confirmation message.
    $original_revision_timestamp = $this->revision->getRevisionCreationTime();

    $this->revision = $this->utilities->prepareRevertedRevision($this->revision, $form_state);
    $this->revision->revision_log = t('Copy of the revision from %date.', ['%date' => $this->dateFormatter->format($original_revision_timestamp)]);
    $this->revision->setRevisionUserId($this->currentUser()->id());
    $this->revision->setRevisionCreationTime($this->time->getRequestTime());
    $this->revision->setChangedTime($this->time->getRequestTime());
    $this->revision->save();

    $this->utilities->setNotice(t(
      '@type: Completed draft-hold. Restored %title revision %revision.',
      [
        '@type' => $this->revision->bundle(),
        '%title' => $this->revision->label(),
        '%revision' => $this->revision->getRevisionId(),
      ]
    ));
    $this->messenger()
      ->addStatus($this->t('@type %title revision from %revision-date has been restored.', [
        '@type' => node_get_type_label($this->revision),
        '%title' => $this->revision->label(),
        '%revision-date' => $this->dateFormatter->format($original_revision_timestamp),
      ]));
    $form_state->setRedirect(
      'entity.node.edit_form',
      ['node' => $this->revision->id()]
    );
  }

}
