<?php

namespace Drupal\third_party_services\Twig;

use Drupal\Core\Url;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Print configurable link in template to modify modal window.
 */
class ConfigurationFormUrlTwigExtension extends \Twig_Extension implements ContainerInjectionInterface {

  /**
   * Instance of the "current_user" service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * ConfigurationController constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Instance of the "current_user" service.
   */
  public function __construct(AccountInterface $account) {
    $this->currentUser = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static($container->get('current_user'));
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return 'third_party_services__configuration_form_url';
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [
      new \Twig_SimpleFunction($this->getName(), [$this, 'getConfigurationFormUrl']),
    ];
  }

  /**
   * Returns URL of route with configuration form for particular user.
   *
   * @param array $dialog_options
   *   Set of options for "OpenDialogCommand".
   *
   * @return \Drupal\Core\Url
   *   Object representation of the URL.
   *
   * @see \Drupal\third_party_services\Controller\ConfigurationController::form()
   */
  public function getConfigurationFormUrl(array $dialog_options): Url {
    return Url::fromRoute(THIRD_PARTY_SERVICES_CONFIGURATION_FORM_ROUTE, [
      'user' => $this->currentUser->id(),
    ], [
      'query' => [
        'dialog_options' => $dialog_options,
      ],
    ]);
  }

}
