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
 * Provides a form for beginning a draft-hold.
 */
class ConfirmStart extends ConfirmFormBase {

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
   * The draft-hold utilities service.
   *
   * @var \Drupal\hold_my_draft\Utilities
   */
  protected $utilities;

  /**
   * Constructs a new ConfirmStart form.
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
    return 'node_hold_my_draft_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to start a draft-hold?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.node.version_history', ['node' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Hold draft and edit published');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('This will start the draft-hold process. You will be able to 
    edit the published revision from %revision-date. After making your edits, 
    you will be able to restore the latest draft.', [
      '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
    ]);
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
    // Begin tracking the draft-hold.
    $this->utilities->startDraftHold($this->revision);

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
      '@type: cloned %title revision %revision.',
      [
        '@type' => $this->revision->bundle(),
        '%title' => $this->revision->label(),
        '%revision' => $this->revision->getRevisionId(),
      ]
    ));
    $this->messenger()
      ->addStatus($this->t('@type %title revision from %revision-date is now editable.', [
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
