<?php

namespace Drupal\social_post_weibo\Plugin\RulesAction;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\rules\Core\RulesActionBase;
use Drupal\social_post\SocialPostManager;
use Drupal\social_post_weibo\Plugin\Network\WeiboPostInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Tweet' action.
 *
 * @RulesAction(
 *   id = "social_post_weibo_tweet",
 *   label = @Translation("Weibo Tweet"),
 *   category = @Translation("Social Post"),
 *   context = {
 *     "status" = @ContextDefinition("string",
 *       label = @Translation("Weibo tweet content"),
 *       description = @Translation("Specifies the status to post.")
 *     )
 *   }
 * )
 */
class Weibo extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * The weibo post network plugin.
   *
   * @var \Drupal\social_post_weibo\Plugin\Network\WeiboPostInterface
   */
  protected $post;

  /**
   * The social post weibo entity storage.
   *
   * @var \Drupal\social_post\SocialPostManager
   */
  protected $postManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /* @var \Drupal\social_post_weibo\Plugin\Network\WeiboPost $weibo_post*/
    $weibo_post = $container->get('plugin.network.manager')->createInstance('social_post_weibo');

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $weibo_post,
      $container->get('social_post.post_manager'),
      $container->get('current_user')
    );
  }

  /**
   * Tweet constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\social_post_weibo\Plugin\Network\WeiboPostInterface $weibo_post
   *   The weibo post network plugin.
   * @param \Drupal\social_post\SocialPostManager $post_manager
   *   The social post manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              WeiboPostInterface $weibo_post,
                              SocialPostManager $post_manager,
                              AccountInterface $current_user) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->post = $weibo_post;
    $this->postManager = $post_manager;
    $this->currentUser = $current_user;

    $this->postManager->setPluginId('social_post_weibo');

  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $accounts = $this->postManager->getAccountsByUserId('social_post_weibo', $this->currentUser->id());
    $status = $this->getContextValue('status');

    /* @var \Drupal\social_post\Entity\SocialPost $account */
    foreach ($accounts as $account) {
      $access_token = json_decode($this->postManager->getToken($account->getProviderUserId()), TRUE);
      $this->post->doPost($access_token, $status);
    }
  }

}
