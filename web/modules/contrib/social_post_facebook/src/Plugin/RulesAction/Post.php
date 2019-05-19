<?php

namespace Drupal\social_post_facebook\Plugin\RulesAction;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\rules\Core\RulesActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_post\SocialPostManager;
use Drupal\social_post_facebook\FacebookPostAuthManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Provides a 'Post' action.
 *
 * @RulesAction(
 *   id = "social_post_facebook",
 *   label = @Translation("Facebook Post"),
 *   category = @Translation("Social Post"),
 *   context = {
 *     "status" = @ContextDefinition("string",
 *       label = @Translation("Post content"),
 *       description = @Translation("Specifies the status to post.")
 *     )
 *   }
 * )
 */
class Post extends RulesActionBase implements ContainerFactoryPluginInterface {
  /**
   * The network plugin manager.
   *
   * @var \Drupal\social_api\Plugin\NetworkManager
   */
  private $networkManager;

  /**
   * The Facebook authentication manager.
   *
   * @var \Drupal\social_post_facebook\FacebookPostAuthManager
   */
  private $facebookManager;

  /**
   * The social post manager.
   *
   * @var \Drupal\social_post\SocialPostManager
   */
  protected $postManager;

  /**
   * The facebook post network plugin.
   *
   * @var \Drupal\social_post_facebook\Plugin\Network\FacebookPostInterface
   */
  protected $facebookPost;

  /**
   * The social post facebook entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $facebookEntity;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;
  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('plugin.network.manager'),
      $container->get('social_post.post_manager'),
      $container->get('facebook_post.social_post_auth_manager'),
      $container->get('logger.factory')

    );
  }

  /**
   * Facebook Post constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   Network plugin manager.
   * @param \Drupal\social_post\SocialPostManager $user_manager
   *   Manages user login/registration.
   * @param \Drupal\social_post_facebook\FacebookPostAuthManager $facebook_manager
   *   Used to manage authentication methods.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Used for logging errors.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManagerInterface $entity_manager,
                              AccountInterface $current_user,
                              NetworkManager $network_manager,
                              SocialPostManager $user_manager,
                              FacebookPostAuthManager $facebook_manager,
                              LoggerChannelFactoryInterface $logger_factory) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->facebookEntity = $entity_manager->getStorage('social_post');
    $this->currentUser = $current_user;
    $this->networkManager = $network_manager;
    $this->postManager = $user_manager;
    $this->facebookManager = $facebook_manager;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Executes the action with the given context.
   *
   * @param string $status
   *   The Post text.
   */
  protected function doExecute($status) {
    $facebook = $this->networkManager->createInstance('social_post_facebook')->getSdk();

    // If facebook client could not be obtained.
    if (!$facebook) {
      drupal_set_message($this->t('Social Auth Facebook not configured properly. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    // Google service was returned, inject it to $googleManager.
    $this->facebookManager->setClient($facebook);

    $accounts = $this->postManager->getList('social_post_facebook', \Drupal::currentUser()->id());

    /* @var \Drupal\social_post_facebook\Entity\FacebookUserInterface $account */
    foreach ($accounts as $account) {
      $access_token = $this->postManager->getToken('social_post_facebook', $account->getSocialNetworkID())->access_token;
      $provider_user_id = $account->getSocialNetworkID();
      $this->facebookManager->requestApiCall($status, $access_token);
    }
  }

}
