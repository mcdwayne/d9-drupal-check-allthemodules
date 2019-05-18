<?php

namespace Drupal\coming_soon\EventSubscriber;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\CurrentPathStack;

/**
 * Manages guests redirections based on the coming soon module configuration.
 */
class ComingSoonRedirectManager implements EventSubscriberInterface {

  /**
   * Current user.
   *
   * @var Drupal\Core\Session\AccountProxyInterface
   */
  private $currentUser;

  /**
   * Url generator.
   *
   * @var Drupal\Core\Routing\UrlGeneratorInterface
   */
  private $urlGenerator;

  /**
   * Config.
   *
   * @var Drupal\Core\Config\ConfigFactoryInterface
   */
  private $config;

  /**
   * Current path.
   *
   * @var Drupal\Core\Path\CurrentPathStack
   */
  private $currentPath;

  /**
   * Construct.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   Current user.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   Url Generator.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Configuration.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   Current path.
   */
  public function __construct(AccountProxyInterface $account, UrlGeneratorInterface $url_generator, ConfigFactoryInterface $config, CurrentPathStack $current_path) {
    $this->currentUser = $account;
    $this->urlGenerator = $url_generator;
    $this->config = $config;
    $this->currentPath = $current_path;
  }

  /**
   * Verifies if there should be a redirection to coming soon page.
   */
  public function checkForRedirection(GetResponseEvent $event) {

    // Get the coming_soon configuration.
    $config = $this->config->get('coming_soon.settings');
    $end_date = date_create($config->get('coming_soon_end_date'));
    $now = date_create(date('Y-m-d'));
    $diff = date_diff($end_date, $now);
    $comingsoon_url = $this->urlGenerator->generateFromRoute('coming_soon.index');
    // Get login url.
    $login_url = $this->urlGenerator->generateFromRoute('user.login');
    $current_path = $this->currentPath->getPath();
    /*
    Check if the user is anonymous & the end date of the coming soon page is
    less than the current.
     */
    $user_roles = $this->currentUser->getRoles();
    // Date & the visited url is different then the login page.
    if ((!empty($user_roles) && in_array('anonymous', $user_roles)) &&
      $diff->days >= 0 && $login_url != $current_path && $comingsoon_url != $current_path) {
      $event->setResponse(new RedirectResponse($comingsoon_url, 301));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkForRedirection'];

    return $events;
  }

}
