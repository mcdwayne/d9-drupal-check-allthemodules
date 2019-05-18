<?php

namespace Drupal\friendship\Controller;

use Drupal\user\Entity\User;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\friendship\FriendshipService;
use Drupal\friendship\Ajax\RebindLinkCommand;
use Drupal\friendship\Ajax\OutdateMessageCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FriendshipController.
 */
class FriendshipController extends ControllerBase {

  /**
   * Friendship Service.
   *
   * @var \Drupal\friendship\FriendshipService
   */
  protected $friendshipService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('friendship.friendship_service')
    );
  }

  /**
   * Constructor.
   *
   * @param \Drupal\friendship\FriendshipService $friendshipService
   *   Friendship service.
   */
  public function __construct(FriendshipService $friendshipService) {
    $this->friendshipService = $friendshipService;
  }

  /**
   * General process action.
   *
   * @param string $action_name
   *   Action name.
   * @param string $uid
   *   User id.
   * @param string $js
   *   Js indicator.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  protected function processLinkAction($action_name, $uid, $js) {
    $response = new AjaxResponse();
    if ($js == 'ajax') {
      $target_user = User::load($uid);

      switch ($action_name) {
        case 'follow':
          if ($this->friendshipService->isHasRelationship($target_user)) {
            $this->friendshipService->follow($target_user);
            $response = $this->getAjaxResponse($target_user);
          }
          else {
            $response = $this->invokeOutdateMessage();
          }
          break;

        case 'unfollow':
          if ($this->friendshipService->isRequestSend($target_user)) {
            $this->friendshipService->unfollow($target_user);
            $response = $this->getAjaxResponse($target_user);
          }
          else {
            $response = $this->invokeOutdateMessage();
          }
          break;

        case 'accept':
          if ($this->friendshipService->isFollowedYou($target_user)) {
            $this->friendshipService->accept($target_user);
            $response = $this->getAjaxResponse($target_user);
          }
          else {
            $response = $this->invokeOutdateMessage();
          }
          break;

        case 'removeFriend':
          if ($this->friendshipService->isFriend($target_user)) {
            $this->friendshipService->removeFriend($target_user);
            $response = $this->getAjaxResponse($target_user);
          }
          else {
            $response = $this->invokeOutdateMessage();
          }
          break;

        case 'declineRequest':
          if ($this->friendshipService->isFollowedYou($target_user)) {
            $this->friendshipService->decline($target_user);
            return $this->redirect('page_manager.page_view_my_friends_my_friends-panels_variant-0');
          }
      }
    }

    return $response;
  }

  /**
   * Follow user.
   *
   * @param string $uid
   *   User id.
   * @param string $js
   *   Js indicator.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function follow($uid, $js = 'nojs') {
    return $this->processLinkAction('follow', $uid, $js);
  }

  /**
   * Unfollow user.
   *
   * @param string $uid
   *   User id.
   * @param string $js
   *   Js indicator.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function unfollow($uid, $js = 'nojs') {
    return $this->processLinkAction('unfollow', $uid, $js);
  }

  /**
   * Accept user.
   *
   * @param string $uid
   *   User id.
   * @param string $js
   *   Js indicator.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function accept($uid, $js = 'nojs') {
    return $this->processLinkAction('accept', $uid, $js);
  }

  /**
   * Remove friend user.
   *
   * @param string $uid
   *   User id.
   * @param string $js
   *   Js indicator.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function removeFriend($uid, $js = 'nojs') {
    return $this->processLinkAction('removeFriend', $uid, $js);
  }

  /**
   * Decline request.
   *
   * @param string $uid
   *   User id.
   */
  public function declineRequest($uid) {
    // Temporary stab.
    return $this->processLinkAction('declineRequest', $uid, 'ajax');
  }

  /**
   * Return ajax response for ajax link.
   *
   * @param \Drupal\user\Entity\User $target_user
   *   User object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response for link.
   */
  protected function getAjaxResponse(User $target_user) {
    $response = new AjaxResponse();

    $link_attributes = $this->friendshipService->getLinkAttributes($target_user);
    $action_url = $link_attributes['#url']->toString();

    $target_user_id = $target_user->id();

    $selector = '.friendship-ajax-link-' . $target_user_id;
    $response->addCommand(new RebindLinkCommand($selector, $action_url, $link_attributes['#title']));

    return $response;
  }

  /**
   * Invoke outdate message.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  protected function invokeOutdateMessage() {
    $response = new AjaxResponse();

    $response->addCommand(new OutdateMessageCommand());
    return $response; 
  }

}
