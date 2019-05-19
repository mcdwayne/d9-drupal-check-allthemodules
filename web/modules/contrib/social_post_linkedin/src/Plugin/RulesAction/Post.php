<?php

namespace Drupal\social_post_linkedin\Plugin\RulesAction;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\rules\Core\RulesActionBase;
use Drupal\social_post_linkedin\Plugin\Network\LinkedInPostInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\social_post\SocialPostManager;

/**
 * Provides a 'Post' action.
 *
 * @RulesAction(
 *   id = "social_post_linkedin",
 *   label = @Translation("LinkedIn Post"),
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

  use UrlGeneratorTrait;

  /**
   * The social post manager.
   *
   * @var \Drupal\social_post\SocialPostManager
   */
  protected $postManager;

  /**
   * The Social Post LinkedIn Network plugin.
   *
   * @var \Drupal\social_post_linkedin\Plugin\Network\LinkedInPostInterface
   */
  protected $linkedInPost;

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
    /* @var \Drupal\social_post_linkedin\Plugin\Network\LinkedInPostInterface $linkedin_post*/
    $linkedin_post = $container->get('plugin.network.manager')->createInstance('social_post_linkedin');

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('social_post.post_manager'),
      $container->get('current_user'),
      $linkedin_post
    );
  }

  /**
   * LinkedIn Post Rules action constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\social_post\SocialPostManager $post_manager
   *   The Social Post manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\social_post_linkedin\Plugin\Network\LinkedInPostInterface $linkedin_post
   *   Used to manage authentication methods.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              SocialPostManager $post_manager,
                              AccountInterface $current_user,
                              LinkedInPostInterface $linkedin_post) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->postManager = $post_manager;
    $this->currentUser = $current_user;
    $this->linkedInPost = $linkedin_post;

    $this->postManager->setPluginId('social_post_linkedin');
  }

  /**
   * Executes the action with the given context.
   *
   * @param string $status
   *   The Post text.
   */
  protected function doExecute($status) {
    $accounts = $this->postManager->getAccountsByUserId('social_post_linkedin', $this->currentUser->id());

    /* @var \Drupal\social_post\Entity\SocialPost $account */
    foreach ($accounts as $account) {
      $access_token = $this->postManager->getToken($account->getProviderUserId());
      $this->linkedInPost->doPost($access_token, $status);
    }
  }

}
