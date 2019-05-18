<?php

namespace Drupal\people\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\people\PeopleManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block to display user account check messages.
 *
 * @Block(
 *   id = "people_user_check_block",
 *   admin_label = @Translation("User check")
 * )
 */
class UserCheckBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current active user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The people manager service.
   *
   * @var \Drupal\people\PeopleManagerInterface
   */
  protected $peopleManager;

  /**
   * Constructs a new SystemBreadcrumbBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current active user.
   * @param \Drupal\people\PeopleManagerInterface $people_manager
   *   The people manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $current_user, PeopleManagerInterface $people_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->peopleManager = $people_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('people.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($this->currentUser->isAuthenticated()) {
      if (!$this->peopleManager->currentPeople()) {
        $message = $this->t('Your account does not associated with a people record, so could not access business information.');
      }
      elseif (!$this->peopleManager->currentOrganization()) {
        $message = $this->t('Your account does not associated with a organization record, so could not access business information.');
      }

      if (isset($message)) {
        return [
          '#markup' => $message,
          '#prefix' => '<div class="messages messages--warning">',
          '#suffix' => '</div>',
        ];
      }
    }
  }

}
