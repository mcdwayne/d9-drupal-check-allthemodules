<?php

namespace Drupal\vud\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\votingapi\Entity\Vote;
use Drupal\votingapi\Entity\VoteType;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for voting.
 *
 * Provides logical methods to the route endpoints.
 * @todo Fix docs.
 * @todo Fix coding standards.
 */
class VotingApiController extends ControllerBase {

  /**
   * Cast a vote.
   *
   * @param $entity_id
   *  EntityId of the referenced entity
   * @param $entity_type_id
   *  EntityTypeId of the referenced entity
   * @param $vote_value
   *  Value of vote to be stored.
   * @param $widget_name
   *   Widget name.
   * @param string $js
   *   Ajax is enabled? Not working now, core bug?
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function vote($entity_type_id, $entity_id, $vote_value, $widget_name, $js) {
    $entity = $this->entityTypeManager()
      ->getStorage($entity_type_id)
      ->load($entity_id);
    $widget = \Drupal::service('plugin.manager.vud')
      ->createInstance($widget_name);

    $vote_storage = $this->entityTypeManager()->getStorage('vote');

    $voteTypeId = \Drupal::config('vud.settings')->get('tag', 'vote');
    $voteType = VoteType::load($voteTypeId);

    $vote_storage->deleteUserVotes(
      $this->currentUser()->id(),
      $voteTypeId,
      $entity_type_id,
      $entity_id
    );

    $this->entityTypeManager()
      ->getViewBuilder($entity_type_id)
      ->resetCache([$entity]);

    $vote = Vote::create(['type' => $voteTypeId]);
    $vote->setVotedEntityId($entity_id);
    $vote->setVotedEntityType($entity_type_id);
    $vote->setValueType($voteType->getValueType());
    $vote->setValue($vote_value);
    $vote->save();

    $this->entityTypeManager()
      ->getViewBuilder($entity_type_id)
      ->resetCache([$entity]);

    $criteria = [
      'entity_type' => $entity_type_id,
      'entity_id' => $entity_id,
      'value_type' => $voteTypeId,
    ];

    if ($js == 'ajax') {
      $response = new AjaxResponse();
      $widget_element = $widget->build($entity);
      $response->addCommand(new ReplaceCommand("#vud-widget-$entity_type_id-$entity_id", $widget_element));
      return $response;
    }

    return new RedirectResponse($entity->toUrl()->toString());
  }

  /**
   * Reset a vote.
   *
   * @param $entity_id
   *  EntityId of the referenced entity
   * @param $entity_type_id
   *  EntityTypeId of the referenced entity
   * @param $widget_name
   *   Widget name.
   * @param string $js
   *   Ajax is enabled? Not working now, core bug?
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function resetVote($entity_type_id, $entity_id, $widget_name, $js){
    $entity = $this->entityTypeManager()
      ->getStorage($entity_type_id)
      ->load($entity_id);
    $widget = \Drupal::service('plugin.manager.vud')
      ->createInstance($widget_name);

    $voteTypeId = \Drupal::config('vud.settings')->get('tag', 'vote');

    $vote_storage = $this->entityTypeManager()->getStorage('vote');

    $vote_storage->deleteUserVotes(
      $this->currentUser()->id(),
      $voteTypeId,
      $entity_type_id,
      $entity_id
    );

    $this->entityTypeManager()
      ->getViewBuilder($entity_type_id)
      ->resetCache([$entity]);

    if ($js == 'ajax') {
      $response = new AjaxResponse();
      $widget_element = $widget->build($entity);
      $response->addCommand(new ReplaceCommand("#vud-widget-$entity_type_id-$entity_id", $widget_element));
      return $response;
    }

    return new RedirectResponse($entity->toUrl()->toString());
  }

  /**
   * Checks if the currentUser is allowed to vote.
   *
   * @return \Drupal\Core\Access\AccessResult|\Drupal\Core\Access\AccessResultAllowed
   *   The access result.
   */
  public function voteAccess() {
    // Check if user has permission to vote.
    if (!vud_can_vote($this->currentUser())) {
      return AccessResult::forbidden();
    }
    else {
      return AccessResultAllowed::allowed();
    }
  }

  /**
   * Checks if the currentUser is allowed to reset vote.
   *
   * @return \Drupal\Core\Access\AccessResult|\Drupal\Core\Access\AccessResultAllowed
   *   The access result.
   */
  public function resetVoteAccess() {
    // Check if user has permission to vote.
    if (!vud_can_reset_vote($this->currentUser())) {
      return AccessResult::forbidden();
    }
    else {
      return AccessResultAllowed::allowed();
    }
  }

}
