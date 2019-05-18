<?php

namespace Drupal\dtuber\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Dtuber controller for Authorization & Revoking user access.
 */
class DTuberController extends ControllerBase {

  protected $dtuberYtService;
  protected $configFactory;
  protected $request;

  /**
   * {@inheritdoc}
   */
  public function __construct($dtuberYoutube, $configFactory, $request) {
    $this->dtuberYtService = $dtuberYoutube;
    $this->configFactory = $configFactory;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('dtuber_youtube_service'),
      $container->get('config.factory'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * Revokes Google Authorization.
   *
   * @param bool $showmsg
   *        (optional) Controls display of authentication revoked message.
   */
  public function revoke($showmsg = TRUE) {
    $config = $this->configFactory->getEditable('dtuber.settings');
    $config->set('access_token', NULL)->save();

    $this->dtuberYtService->revokeAuth();

    if ($showmsg) {
      drupal_set_message($this->t('Authentication Revoked. Need re authorization from Google.'));
    }

    return $this->redirect('dtuber.configform');
  }

  /**
   * Authorizes User.
   */
  public function authorize() {
    // Handles dtuber/authorize authorization from google.
    $code = $this->request->query->get('code');
    $error = $this->request->query->get('error');
    // Authorize current request.
    $this->dtuberYtService->authorizeClient($code);

    drupal_set_message($this->t('New Token Authorized.'));

    if ($code) {
      if ($this->dtuberYtService->youTubeAccount() === FALSE) {
        drupal_get_messages();
        drupal_set_message($this->t('YouTube account not configured properly.'), 'error');
        $this->revoke(FALSE);
      }

    }
    elseif ($error == 'access_denied') {
      drupal_set_message($this->t('Access Rejected! grant application to use your account.'), 'error');
    }
    // Redirect to configform.
    return $this->redirect('dtuber.configform');

  }

}
