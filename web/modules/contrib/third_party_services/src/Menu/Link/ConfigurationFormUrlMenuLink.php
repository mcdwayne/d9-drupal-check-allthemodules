<?php

namespace Drupal\third_party_services\Menu\Link;

use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Menu\StaticMenuLinkOverridesInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\third_party_services\Form\MenuLinkForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Link to configuration form to place and customized in menu.
 */
class ConfigurationFormUrlMenuLink extends MenuLinkDefault {

  /**
   * Instance of the "current_user" service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;
  /**
   * {@inheritdoc}
   */
  protected $overrideAllowed = [
    'title' => 1,
    'parent' => 1,
    'weight' => 1,
    'enabled' => 1,
    'options' => 1,
    'menu_name' => 1,
  ];

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    StaticMenuLinkOverridesInterface $static_override,
    AccountInterface $account
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $static_override);

    $this->currentUser = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu_link.static.overrides'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName(): string {
    return THIRD_PARTY_SERVICES_CONFIGURATION_FORM_ROUTE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(): array {
    $parameters = parent::getRouteParameters();
    $parameters['user'] = $parameters['user'] ?? $this->currentUser->id();

    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(): string {
    return parent::getTitle() ?: $this->t('Third-party services');
  }

  /**
   * {@inheritdoc}
   */
  public function isDeletable(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isResettable(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormClass(): string {
    return MenuLinkForm::class;
  }

}
