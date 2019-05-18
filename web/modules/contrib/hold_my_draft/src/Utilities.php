<?php

namespace Drupal\hold_my_draft;

use Drupal\content_moderation\ModerationInformation;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Session\AccountProxy;
use Drupal\node\NodeInterface;
use Drupal\Core\Url;
use stdClass;

/**
 * Class Utilities.
 *
 * A general service for managing draft-hold global functionality.
 *
 * @package Drupal\hold_my_draft
 */
class Utilities extends ControllerBase {

  /**
   * The Moderation Information service from content_moderation.
   *
   * @var \Drupal\content_moderation\ModerationInformation
   */
  protected $moderationInformation;

  /**
   * The draft-hold storage service.
   *
   * @var \Drupal\hold_my_draft\StorageManager
   */
  protected $storeManager;

  /**
   * The Drupal core user service.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The core messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * The draft-hold logging service.
   *
   * @var \Drupal\hold_my_draft\Logger
   */
  protected $logger;

  /**
   * The core entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Utilities constructor.
   *
   * @param \Drupal\content_moderation\ModerationInformation $moderationInformation
   *   The Core content_moderation moderation information service.
   * @param \Drupal\hold_my_draft\StorageManager $storeManager
   *   The draft-hold storage service.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   The Drupal Core current user service.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The Drupal Core messenger service.
   * @param \Drupal\hold_my_draft\Logger $logger
   *   The draft-hold logger service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityManager
   *   The core entity manager service.
   */
  public function __construct(
    ModerationInformation $moderationInformation,
    StorageManager $storeManager,
    AccountProxy $currentUser,
    Messenger $messenger,
    Logger $logger,
    EntityTypeManagerInterface $entityManager
  ) {
    $this->moderationInformation = $moderationInformation;
    $this->storeManager = $storeManager;
    $this->currentUser = $currentUser;
    $this->messenger = $messenger;
    $this->logger = $logger;
    $this->entityManager = $entityManager;
  }

  /**
   * Checks to see if there are forward revisions.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Expects a node object.
   *
   * @return bool
   *   Can start a draft-hold?
   */
  public function isDraftHoldable(NodeInterface $node) {
    $latest_revision = $this->getLatestRevisionId($node);
    $default_revision = $this->getDefaultRevisionId($node);

    // Only hold a draft if there's a forward revision.
    return $latest_revision !== $default_revision;
  }

  /**
   * Get the DefaultRevisionId which is the Published ID.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Expects a node object.
   *
   * @return int|null|string
   *   The current published revision id.
   */
  public function getDefaultRevisionId(NodeInterface $node) {
    return $this->moderationInformation->getDefaultRevisionId('node', $this->cleanId($node));
  }

  /**
   * Get the LatestRevisionId.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Expects a node object.
   *
   * @return int|null|string
   *   The current published revision id.
   */
  public function getLatestRevisionId(NodeInterface $node) {
    return $this->moderationInformation->getLatestRevisionId('node', $this->cleanId($node));
  }

  /**
   * Begin the draft-hold tracking.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Expects a node object.
   *
   * @throws \Exception
   */
  public function startDraftHold(NodeInterface $node) {

    $draftHold = $this->getDraftHoldInfo($node);
    // First things first: confirm there is no in progress draft-hold already.
    if ($this->isInProgress($draftHold)) {
      // Have a freakout, kill the existing one.
      $this->killDraftHold($node);
    }
    $nid = $this->cleanId($node);
    $vid_start = $this->getDefaultRevisionId($node);
    $vid_hold = $this->getLatestRevisionId($node);
    $uid = (int) $this->currentUser->id();

    $this->storeManager->init($nid, $uid, $vid_start, $vid_hold);
  }

  /**
   * Update the draft-hold storage with the completion decision.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Expects a Node object.
   * @param bool $revert
   *   Was the revision reverted?
   */
  public function endDraftHold(NodeInterface $node, bool $revert = TRUE) {
    $nid = $this->cleanId($node);
    $this->storeManager->conclude($nid, $revert);
  }

  /**
   * Something bad happened and we should abort the running draft-hold.
   *
   * Not for public consumption.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Expects a node object.
   */
  protected function killDraftHold(NodeInterface $node) {
    $nid = $this->cleanId($node);
    $this->storeManager->abandon($nid);
  }

  /**
   * Retrieve the most recent draft-hold info for this node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Expects a Node object.
   *
   * @return object
   *   Draft-hold data
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getDraftHoldInfo(NodeInterface $node) {
    $nid = $this->cleanId($node);
    $draftHolds = $this->storeManager->getDraftHoldInformation($nid);
    if (isset($draftHolds{0})) {
      // There could possibly be multiple results returned from a query.
      // We want the one sorted on top of the stack.
      $draftHold = $draftHolds{0};
      if ($draftHold->uid) {
        $draftHold->user = $this->entityManager->getStorage('user')->load($draftHold->uid)->getDisplayName();
      }
    }
    $draftHold = $draftHolds{0} ?? (object) [''];
    return $draftHold;
  }

  /**
   * Check if there is a draft-hold in progress.
   *
   * @param \stdClass $draftHold
   *   Expects a node object.
   *
   * @return bool
   *   Is there an in progress draft-hold right now?
   */
  public function isInProgress(stdClass $draftHold) {

    if (isset($draftHold->status) && $draftHold->status == 'In progress') {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Retrieves the user's display name who created the draft-hold.
   *
   * @param \stdClass $draftHold
   *   Expects a draft-hold query stdClass object from getDraftHoldInfo().
   *
   * @return string
   *   The user's display name, if we have it.
   */
  public function getUser(stdClass $draftHold) {
    $user = '';
    if (isset($draftHold->user)) {
      $user = $draftHold->user;
    }
    return $user;
  }

  /**
   * Retrieves the draft being held by the draft-hold process.
   *
   * @param \stdClass $draftHold
   *   Expects a draft-hold query stdClass object from getDraftHoldInfo().
   *
   * @return int|null
   *   Returns the revision id for the held draft, if we have it.
   */
  public function getHeldRevision(stdClass $draftHold) {
    $held_revision = NULL;
    if (isset($draftHold->start_latest_revision)) {
      $held_revision = (int) $draftHold->start_latest_revision;
    }
    return $held_revision;
  }

  /**
   * Retrieves the latest timestamp on the draft-hold.
   *
   * @param \stdClass $draftHold
   *   Expects a draft-hold query stdClass object from getDraftHoldInfo().
   *
   * @return int|null
   *   The unix timestamp of the draft-hold, if we have it.
   */
  public function getDraftHoldTime(stdClass $draftHold) {
    $time = NULL;
    if (isset($draftHold->hold_time)) {
      $time = (int) $draftHold->hold_time;
    }
    return $time;
  }

  /**
   * Prepares a revision to be reverted.
   *
   * @param \Drupal\node\NodeInterface $revision
   *   The revision to be reverted.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current state of the form.
   *
   * @return \Drupal\node\NodeInterface
   *   The prepared revision ready to be stored.
   */
  public function prepareRevertedRevision(NodeInterface $revision, FormStateInterface $formState) {
    $revision->setNewRevision();
    $revision->isDefaultRevision(TRUE);

    return $revision;
  }

  /**
   * Create a message with current draft-hold status and actions.
   *
   * @param \stdClass $draftHold
   *   Expects a draft-hold query stdClass object from getDraftHoldInfo().
   * @param \Drupal\node\NodeInterface $node
   *   Expects a node object.
   */
  public function generateMessage(stdClass $draftHold, NodeInterface $node) {
    $held_draft = $this->getHeldRevision($draftHold);
    $nid = $this->cleanId($node);
    $canComplete = $this->currentUser->hasPermission('complete draft-hold');

    $cancelRoute = Url::fromRoute('hold_my_draft.draft_hold_cancel', [
      'node' => $nid,
      'node_revision' => $held_draft,
    ])->toString();
    $completeRoute = Url::fromRoute('hold_my_draft.draft_hold_complete', [
      'node' => $nid,
      'node_revision' => $held_draft,
    ])->toString();
    $viewDraftRoute = Url::fromRoute('entity.node.revision', [
      'node' => $nid,
      'node_revision' => $held_draft,
    ])->toString();

    if ($canComplete) {
      $this->messenger->addStatus(
        $this->t('<p>There is a draft-hold in progress for this page: Started by @user, 
          holding unpublished revision from @time (<a href="@link-view">view held revision</a>). <br>You can 
          <strong>complete</strong> or <strong>cancel</strong> this draft-hold at anytime.</p>
          <p><a href="@link-complete" class="button button--primary" aria-label="Complete draft hold">Complete</a> 
          <a href="@link-cancel" class="button" aria-label="Cancel draft hold">Cancel</a></p>',
          [
            '@user' => $this->getUser($draftHold),
            '@revision' => $held_draft,
            '@time' => date('M j, Y: H:i', $this->getDraftHoldTime($draftHold)),
            '@link-complete' => $completeRoute,
            '@link-cancel' => $cancelRoute,
            '@link-view' => $viewDraftRoute,
          ])
      );
    }
    else {
      $this->messenger->addStatus(
        $this->t('There is a draft-hold in progress for this page: Started by @user, 
          holding unpublished revision from @time (<a href="@link-view">view held revision</a>).
          <br>You do not have permission to complete or cancel this draft-hold.',
          [
            '@user' => $this->getUser($draftHold),
            '@revision' => $held_draft,
            '@time' => date('M j, Y: H:i', $this->getDraftHoldTime($draftHold)),
            '@link-view' => $viewDraftRoute,
          ])
      );
    }

  }

  /**
   * A helper function for providing properly typed node id.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Expects a node object.
   *
   * @return int
   *   A true integer representation of node id.
   */
  protected function cleanId(NodeInterface $node) {
    return (int) $node->id();
  }

  /**
   * Throw a hold my draft error in the logger.
   *
   * @param string $message
   *   Expects a message string.
   */
  public function throwError(string $message) {
    $this->logger->setError($message);
  }

  /**
   * Set a hold my draft notice in the logger.
   *
   * @param string $message
   *   Expects a message string.
   */
  public function setNotice(string $message) {
    $this->logger->setNotice($message);
  }

}
