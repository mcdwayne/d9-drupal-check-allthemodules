<?php

namespace Drupal\friends\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\mailsystem\MailsystemManager;
use Drupal\activity_creator\Plugin\ActivityActionManager;

use Drupal\user\UserInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\friends\Entity\FriendsInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\friends\FriendsService;

/**
 * Class FriendsAPIController.
 */
class FriendsAPIController extends ControllerBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * Drupal\Core\Messenger\MessengerInterface definition.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;
  /**
   * Drupal\mailsystem\MailsystemManager definition.
   *
   * @var \Drupal\mailsystem\MailsystemManager
   */
  protected $mailManager;
  /**
   * Drupal\activity_creator\Plugin\ActivityActionManager definition.
   *
   * @var \Drupal\activity_creator\Plugin\ActivityActionManager
   */
  protected $activityManager;
  /**
   * Drupal\friends\FriendsStorage definition.
   *
   * @var \Drupal\friends\FriendsStorage
   */
  protected $friendsStorage;
  /**
   * Drupal\friends\FriendsService definition.
   *
   * @var \Drupal\friends\FriendsService
   */
  protected $friendsService;

  /**
   * Cache Tags Invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Constructs a new FriendsAPIController object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    MessengerInterface $messenger,
    MailsystemManager $plugin_manager_mail,
    ActivityActionManager $plugin_manager_activity_action_processor,
    FriendsService $friends_service,
    CacheTagsInvalidatorInterface $cache_tags_invalidator
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->mailManager = $plugin_manager_mail;
    $this->activityManager = $plugin_manager_activity_action_processor;
    $this->friendsStorage = $entity_type_manager->getStorage('friends');
    $this->friendsService = $friends_service;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('plugin.manager.mail'),
      $container->get('plugin.manager.activity_action.processor'),
      $container->get('friends.default'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * Creates a friend request of the specified type.
   *
   * @param \Drupal\user\UserInterface $user
   *   The recipient user of the request.
   * @param string $type
   *   The machine name of the type of friendship to request.
   *
   * @return Drupal\Core\Ajax\AjaxResponse
   *   An ajax Response object removing the request link. And opening a modal
   *   saying that the request was successful.
   */
  public function request(UserInterface $user, string $type) {
    $response = new AjaxResponse();

    $allowed_types = $this->friendsService->getAllowedTypes();

    $friends = $this->friendsStorage->create([
      'recipient' => $user->id(),
      'friends_type' => $type,
    ]);
    $friends->save();

    $content = [
      '#type' => 'item',
      '#markup' => $this->t('You have Requested to add this user as your @type', [
        '@type' => $allowed_types[$type],
      ]),
    ];
    $response->addCommand(new RemoveCommand('#friends-api-add-as--' . $type));

    $response->addCommand(new OpenModalDialogCommand(
      $this->t('@type',
      [
        '@type' => $allowed_types[$type],
      ]),
      $content,
      ['width' => '50%']
    ));
    $this->cacheTagsInvalidator->invalidateTags(['friends:add:' . $user->id() . ':type:' . $type]);

    return $response;
  }

  /**
   * Responds to a friend request with a given status.
   *
   * @param \Drupal\friends\Entity\FriendsInterface $friends
   *   The friends entity to update.
   * @param string $status
   *   The machine name of status to apply.
   *
   * @return Drupal\Core\Ajax\AjaxResponse
   *   An ajax Response object removing the apply links.
   */
  public function response(FriendsInterface $friends, string $status) {
    $response = new AjaxResponse();
    $friends->set('friends_status', $status)->save();
    $response->addCommand(new RemoveCommand('.friends-api-response--' . $friends->id()));

    return $response;
  }

}
