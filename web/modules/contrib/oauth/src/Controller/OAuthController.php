<?php

/**
 * @file
 * Contains \Drupal\oauth\Controller\OAuthController.
 */

namespace Drupal\oauth\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\user\UserDataInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for oauth routes.
 */
class OAuthController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The URL generator service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserData
   */
  protected $user_data;

  /**
   * Constructs an OauthController object.
   *
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   *
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator service.
   */
  public function __construct(UserDataInterface $user_data, LinkGeneratorInterface $link_generator) {
    $this->user_data = $user_data;
    $this->linkGenerator = $link_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\user\UserDataInterface $user_data */
    $user_data = $container->get('user.data');

    /** @var \Drupal\Core\Utility\LinkGeneratorInterface $link_generator */
    $link_generator = $container->get('link_generator');

    return new static($user_data, $link_generator);
  }

  /**
   * Returns the list of consumers for a user.
   *
   * @param \Drupal\user\UserInterface $user
   *   A user account object.
   *
   * @return string
   *   A HTML-formatted string with the list of OAuth consumers.
   */
  public function consumers(UserInterface $user) {
    $list = array();

    $list['#cache']['tags'] = array(
      'oauth:' => $user->id(),
    );

    $list['heading']['#markup'] = $this->linkGenerator->generate($this->t('Add consumer'), Url::fromRoute('oauth.user_consumer_add', array('user' => $user->id())));

    // Get the list of consumers.
    $result = $this->user_data->get('oauth', $user->id());

    // Define table headers.
    $list['table'] = array(
      '#theme' => 'table',
      '#header' => array(
        'consumer_key' => array(
          'data' => $this->t('Consumer key'),
        ),
        'consumer_secret' => array(
          'data' => $this->t('Consumer secret'),
        ),
        'operations' => array(
          'data' => $this->t('Operations'),
        ),
      ),
      '#rows' => array(),
    );

    // Add existing consumers to the table.
    foreach ($result as $key => $consumer) {
      $list['table']['#rows'][] = array(
        'data' => array(
          'consumer_key' => $key,
          'consumer_secret' => $consumer['consumer_secret'],
          'operations' => array(
            'data' => array(
              '#type' => 'operations',
              '#links' => array(
                'delete' => array(
                  'title' => $this->t('Delete'),
                  'url' => Url::fromRoute('oauth.user_consumer_delete', array('user' => $user->id(), 'key' => $key)),
                ),
              ),
            ),
          ),
        ),
      );
    }

    $list['table']['#empty'] = $this->t('There are no OAuth consumers.');

    return $list;
  }

}
