<?php

namespace Drupal\ptalk_block_user\Controller;

use Drupal\ptalk\MessageInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for ptalk_block_user module.
 */
class BlockUserController extends ControllerBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('entity.manager'),
      $container->get('current_user')
    );
  }

  /**
   * Constructs a controller for ptalk_block_user module.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(FormBuilderInterface $form_builder, EntityManagerInterface $entity_manager, AccountInterface $current_user) {
    $this->formBuilder = $form_builder;
    $this->entityManager = $entity_manager;
    $this->currentUser = $current_user;
  }

  /**
   * Constructs the form to unblock blocked user.
   *
   * @param string $user
   *   The user id to unblock.
   *
   * @return array
   *   A renderable array with form to unblock blocked user.
   */
  public function unblockUserForm($user) {
    $build = [];
    // Create unblock author form.
    $message = $this->entityManager()->getStorage('ptalk_message')->create([
      'user' => $user,
    ]);

    $build['unblock_author'] = $this->entityFormBuilder()->getForm($message, 'unblock_author');

    return $build;
  }

  /**
   * The _custom_access callback for the blocking form.
   *
   * @param \Drupal\ptalk\MessageInterface $ptalk_message
   *   The ptalk_message entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   An access result.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function blockAuthorAccess(MessageInterface $ptalk_message) {
    $message = $ptalk_message;
    $thread = $message->getThread();
    $access_result = AccessResult::allowedIf($thread->participantOf($this->currentUser));
    if ($access_result->isAllowed()) {
      // Do not allow blocking already blocked author or do not allow author blocking himself
      // and do not allow do any actions on the deleted message.
      if ($message->index->is_blocked || $message->isCurrentUserOwner() || $message->isDeleted()) {
        throw new NotFoundHttpException;
      }
    }

    return $access_result;
  }

  /**
   * The _custom_access callback for the unblocking form.
   *
   * @param \Drupal\ptalk\MessageInterface $ptalk_message
   *   The ptalk_message entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   An access result.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function unblockAuthorAccess(MessageInterface $ptalk_message) {
    $message = $ptalk_message;
    $thread = $message->getThread();
    $access_result = AccessResult::allowedIf($thread->participantOf($this->currentUser));
    if ($access_result->isAllowed()) {
      if (is_null($message->index->is_blocked) || $message->isCurrentUserOwner() || $message->isDeleted()) {
        throw new NotFoundHttpException;
      }
    }

    return $access_result;
  }

  /**
   * The _custom_access callback for the unblocking form.
   *
   * @param string $user
   *   The blocked user id to unblock.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   An access result.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function unblockUserAccess($user) {
    $author = user_load($user);
    $access_result = AccessResult::allowedIf($this->currentUser->hasPermission('read private conversation'));
    if ($access_result->isAllowed()) {
      // If author is not a valid user or author is not blocked by user throw page not found.
      if (is_null($author) || !ptalk_block_user_author_is_blocked($author, $this->currentUser)) {
        throw new NotFoundHttpException;
      }
    }

    return $access_result;
  }

}
