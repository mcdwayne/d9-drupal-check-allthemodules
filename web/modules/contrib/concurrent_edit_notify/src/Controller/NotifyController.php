<?php

namespace Drupal\concurrent_edit_notify\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\concurrent_edit_notify\Service\ConcurrentToken;
use Drupal\Core\Utility\Token;
use Drupal\Core\Render\Renderer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Concurrent edit Notify module routes.
 */
class NotifyController extends ControllerBase {

  /**
   * The Connection object.
   *
   * @var Drupal\concurrent_edit_notify\Service\ConcurrentToken
   */
  protected $concurrentToken;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The render object.
   *
   * @var Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * NotifyController constructor.
   *
   * @param Drupal\concurrent_edit_notify\Service\ConcurrentToken $concurrent_token
   *   Concurrent token object.
   * @param \Drupal\Core\Utility\Token $token
   *   Token object.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Renderer object.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   */
  public function __construct(ConcurrentToken $concurrent_token, Token $token, Renderer $renderer, AccountInterface $current_user) {
    $this->concurrentToken = $concurrent_token;
    $this->token = $token;
    $this->renderer = $renderer;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('concurrent_edit_notify.concurrent_token'),
      $container->get('token'),
      $container->get('renderer'),
      $container->get('current_user')
    );
  }

  /**
   * Check if a new revision exist for a current node.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   * @param \Drupal\node\NodeInterface $node
   *   The node that need to be checked.
   * @param int $vid
   *   Current vid to check for.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function check(Request $request, NodeInterface $node, $vid) {
    $response = NULL;

    if ($node->isTranslatable()) {
      // Get tokens registered with nide of $node.
      $tokens = $this->concurrentToken->load($node->id(), $node->get('langcode')->value);
      $first_token = $this->concurrentToken->loadFirst($node->id(), $node->get('langcode')->value);
      // Check if multiple users are editing the node.
      if ((count($tokens) > 1) && ($this->currentUser->id() != $first_token->uid)) {
        // Get current settings to build the message.
        $config = $this->config('concurrent_edit_notify.settings');

        $message = $config->get('message_published');
        $type = "warning";

        $message = $this->token->replace($message, ['node' => $node]);

        // Mimic status messages.
        $status_message = [
          '#markup' => $message,
          '#prefix' => '<div class="messages messages--' . $type . ' notify-' . $vid . '">',
          '#suffix' => '</div>',
        ];
        // Current token.
        $current_token = [
          'nid' => $node->id(),
          'langcode' => $node->get('langcode')->value,
          'uid' => $this->currentUser->id(),
        ];
        // Check if token exists and warning msg is not displayed.
        if (($this->concurrentToken->check($current_token)) & !$this->concurrentToken->isDisplayed($node->id(), $node->get('langcode')->value, $this->currentUser->id())) {
          $this->concurrentToken->setDisplayed($node->id(), $node->get('langcode')->value, $this->currentUser->id());
          $response['display'] = TRUE;
          $response['message'] = $this->renderer->render($status_message);
        }
      }
    }

    return new JsonResponse($response);
  }

  /**
   * Reset tokens.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   * @param \Drupal\node\NodeInterface $node
   *   The node that need to be checked.
   * @param int $vid
   *   Current vid to check for.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function reset(Request $request, NodeInterface $node, $vid) {
    $response = NULL;

    if ($node->isTranslatable()) {
      $this->concurrentToken->delete($node->id(), $node->get('langcode')->value);
      $response = TRUE;
    }

    return new JsonResponse($response);
  }

}
