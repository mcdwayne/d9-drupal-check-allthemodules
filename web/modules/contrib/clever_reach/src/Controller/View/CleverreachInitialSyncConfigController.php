<?php

namespace Drupal\clever_reach\Controller\View;

use Drupal\clever_reach\Component\Infrastructure\ConfigService;
use Drupal\Core\Config\ImmutableConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Initial Sync View Controller.
 *
 * @see template file cleverreach-initial-sync.html.twig
 */
class CleverreachInitialSyncConfigController extends CleverreachResolveStateController {
  const CURRENT_STATE_CODE = 'initialsync_config';
  const TEMPLATE = 'cleverreach_initial_sync_config';
  /**
   * Instance of ImmutableConfig class.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $userSettings;

  /**
   * CleverreachInitialSyncConfigController constructor.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $userSettings
   *   User settings immutable config.
   */
  public function __construct(ImmutableConfig $userSettings) {
    $this->userSettings = $userSettings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('config.factory')->get('user.settings')
    );
  }

  /**
   * Callback for the cleverreach.cleverreach.initialsync route.
   *
   * @return array
   *   Template variables.
   */
  public function content() {
    $this->dispatch();
    $userInfo = $this->getConfigService()->getUserInfo();

    return [
      '#recipient_id' => $userInfo['id'],
      '#urls' => [
        'logo_url' => $this->getThemePath('images/icon_quickstartmailing.svg'),
        'help_url' => ConfigService::CLEVERREACH_HELP_URL,
        'configuration_url' => $this->getControllerUrl('configuration'),
      ],
      '#theme' => self::TEMPLATE,
      '#attached' => [
        'library' => ['clever_reach/cleverreach-initial-sync-config-view'],
      ],
    ];
  }

}
