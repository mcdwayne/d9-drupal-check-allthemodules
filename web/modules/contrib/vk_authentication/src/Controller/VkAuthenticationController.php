<?php

namespace Drupal\vk_authentication\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;
use Drupal\vk_authentication\Vk\VkAuthentication;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Returns responses for VkAuthentication module routes.
 */
class VkAuthenticationController extends ControllerBase {

  /**
   * Service "vk_authentication.vk_authentication".
   *
   * @var \Drupal\vk_authentication\Vk\VkAuthentication
   */
  private $vkAuthentication;

  /**
   * Service "messenger".
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $vk_authentication = $container->get('vk_authentication.vk_authentication');
    $messenger = $container->get('messenger');
    $currentUser = $container->get('current_user');

    return new static($currentUser, $vk_authentication, $messenger);
  }

  /**
   * Getting a 'vk_authentication.vk_authentication' service.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current active user service.
   * @param \Drupal\vk_authentication\Vk\VkAuthentication $vk_authentication
   *   VkAuthentication class.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Drupal messenger service.
   */
  public function __construct(AccountProxyInterface $currentUser,
                              VkAuthentication $vk_authentication,
                              MessengerInterface $messenger) {
    $this->currentUser = $currentUser;
    $this->vkAuthentication = $vk_authentication;
    $this->messenger = $messenger;
  }

  /**
   * Redirects the user to Vk.
   */
  public function loginPath() {
    // Checking if current user is logged in.
    if ($this->currentUser->isAuthenticated()) {
      $this->messenger->addMessage(
        $this->t('You are already authenticated')
      );
      return [
        '#attached' => [
          'library' => [
            'vk_authentication/vk_authentication',
          ],
          'drupalSettings' => [
            'vk_authentication' => [
              'vk_authenticationJS' => [
                'pagesBack' => -1,
                'redirectTimeout' => 3000,
              ],
            ],
          ],
        ],
      ];
    }
    // If not logged in, making authentication.
    return new Response($this->vkAuthentication->redirectToVk());
  }

  /**
   * Getting Code parameter and making authentication.
   */
  public function makeAuthentication() {
    // Get 'code' parameter from a request.
    $vkCode = Request::createFromGlobals()->query->get('code');
    // If got 'code' then making authentication.
    if ($vkCode != '') {
      $result = $this->vkAuthentication->makeAuthentication($vkCode);

      if ($result != FALSE) {
        return [
          '#attached' => [
            'library' => [
              'vk_authentication/vk_authentication',
            ],
            'drupalSettings' => [
              'vk_authentication' => [
                'vk_authenticationJS' => [
                  'pagesBack' => -2,
                  'redirectTimeout' => 10000,
                ],
              ],
            ],
          ],
        ];
      }
    }
    // If no code found.
    return [
      '#title' => 'No code found',
    ];
  }

}
