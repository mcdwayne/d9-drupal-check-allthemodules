<?php

namespace Drupal\freelinking\Plugin\freelinking;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\freelinking\Plugin\FreelinkingPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Freelinking user plugin.
 *
 * @Freelinking(
 *   id = "user",
 *   title = @Translation("User"),
 *   weight = 0,
 *   hidden = false,
 *   settings = {  }
 * )
 */
class User extends FreelinkingPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Current user account interface.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Initialize method.
   *
   * @param array $configuration
   *   The configuration array.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin definition array.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, AccountProxyInterface $currentUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndicator() {
    return '/(u|user|username|uid|userid)$/A';
  }

  /**
   * {@inheritdoc}
   */
  public function getTip() {
    return $this->t('Click to view user profile.');
  }

  /**
   * {@inheritdoc}
   */
  public function buildLink(array $target) {
    if ($this->currentUser->hasPermission('access user profiles')) {
      // Find user account.
      $account = $this->entityTypeManager->getStorage('user')->load($target['dest']);
      if (NULL !== $account) {
        $link = [
          '#type' => 'link',
          '#title' => $account->getDisplayName(),
          '#url' => Url::fromRoute('entity.user.canonical', ['user' => $account->id()], ['language' => $target['language']]),
          '#attributes' => [
            'title' => $this->getTip(),
          ],
        ];
      }
      else {
        $link = [
          '#theme' => 'freelink_error',
          '#plugin' => 'user',
          '#message' => $this->t('User %user not found', ['%user' => $target['dest']]),
        ];
      }
    }
    else {
      $link = [
        '#theme' => 'freelink_error',
        '#plugin' => 'user',
        '#message' => $this->t('Unauthorized to view user profile.'),
      ];
    }

    return $link;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

}
