<?php

namespace Drupal\perimeter\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Database\Database;
use Drupal\ban\BanIpManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * On page not found events, ban the IP if the request is suspicious.
 */
class PerimeterSubscriber implements EventSubscriberInterface {
  protected $loggerFactory;
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory) {
    $this->loggerFactory = $logger_factory;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['kernel.exception'][] = ['handleBannedUrls'];

    return $events;
  }

  /**
   * On page not found events, ban the IP if the request is suspicious.
   */
  public function handleBannedUrls(Event $event) {
    $exception = $event->getException();
    if ($exception instanceof NotFoundHttpException) {
      $request_path = $event->getRequest()->getPathInfo();
      $bannedPatterns = $this->configFactory->get('perimeter.settings')->get('not_found_exception_patterns');
      foreach ($bannedPatterns as $pattern) {
        $pattern = trim($pattern);
        if (preg_match($pattern, $request_path)) {
          $connection = Database::getConnection();
          $banManager = new BanIpManager($connection);
          $banManager->banIp($event->getRequest()->getClientIp());
          $this->loggerFactory->get('Perimeter')->notice('Banned: %ip for requesting %pattern <br />Source: %source <br /> User Agent: %browser',
            [
              '%ip' => $event->getRequest()->getClientIp(),
              '%pattern' => Xss::filter($request_path),
              '%source' => isset($_SERVER['HTTP_REFERER']) ? Xss::filter($_SERVER['HTTP_REFERER']) : '',
              '%browser' => isset($_SERVER['HTTP_USER_AGENT']) ? Xss::filter($_SERVER['HTTP_USER_AGENT']) : '',
            ]);
          break;
        }
      }
    }
  }

}
