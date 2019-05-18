<?php

namespace Drupal\client_connection_example\Plugin\Block;

use Drupal\user\Entity\User;
use Drupal\client_connection\ClientConnectionManager;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'ExampleBlock' block.
 *
 * @Block(
 *  id = "client_connection_example_block",
 *  admin_label = @Translation("Example Client Connection Posts Block"),
 * )
 */
class ExampleBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Client Connection manager.
   *
   * @var \Drupal\client_connection\ClientConnectionManager
   */
  protected $clientManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientConnectionManager $client_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->clientManager = $client_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.client_connection')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $user = User::load(\Drupal::currentUser()->id());
    $contexts['user'] = new Context(new ContextDefinition('entity:user', t('Current User'), FALSE), $user);

    /** @var \Drupal\client_connection_example\Plugin\ClientConnection\ExampleConnection $plugin */
    $plugin = $this->clientManager->resolveInstance('example', $contexts);

    $build['inner']['#markup'] = '';

    if ($plugin && $posts = $plugin->getPosts()) {
      $post_count = 0;
      $build['inner']['#markup'] .= '<p>Here are some posts:</p>';
      $build['inner']['#markup'] .= '<ul>';
      foreach ($posts as $post) {
        if ($post_count > 5) {
          break;
        }
        $build['inner']['#markup'] .= '<li>' . $post['title'] . '</li>';
        $post_count++;
      }
      $build['inner']['#markup'] .= '</ul>';
    }
    else {
      $build['inner']['#markup'] .= '<p>No posts found</p>';
    }

    return $build;
  }

}
