<?php

namespace Drupal\login_notification;

use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Define login notification manager.
 */
class LoginNotificationManager implements LoginNotificationManagerInterface {

  /**
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Login notification manager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Condition\ConditionManager $condition_manager
   */
  public function __construct(
    ConditionManager $condition_manager,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->conditionManager = $condition_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function invokeLoginNotifications(AccountInterface $user) {
    $contexts = ['user' => $user];

    /** @var \Drupal\login_notification\Entity\LoginNotificationInterface $notification */
    foreach ($this->getLoginNotifications() as $notification) {
      $condition_verdicts = [];

      foreach ($notification->getActiveConditions() as $plugin_id => $condition) {
        /** @var \Drupal\Core\Condition\ConditionInterface $instance */
        $instance = $this
          ->conditionManager
          ->createInstance($plugin_id, $condition['configuration']);

        // Check if the condition instance needs a context.
        if ($context_definitions = $instance->getContextDefinitions()) {
          $definitions = array_flip(array_keys($context_definitions));

          foreach (array_intersect_key($contexts, $definitions) as $name => $context) {
            $instance->setContextValue($name, $context);
          }
        }
        $verdict = $instance->evaluate();

        // If we met one condition then bail out early if the verdict is true.
        if ($verdict && !$notification->conditionsMetAll()) {
          $notification->render($contexts);
          continue 1;
        }
        $condition_verdicts[] = $verdict;
      }
      $unique_verdicts = array_unique($condition_verdicts);

      // It we met all conditions then render out the notification message.
      if (count($unique_verdicts) === 1 && $unique_verdicts[0] === TRUE) {
        $notification->render($contexts);
      }
    }
  }

  /**
   * Get all login notifications.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getLoginNotifications() {
    return $this
      ->entityTypeManager
      ->getStorage('login_notification')
      ->loadMultiple();
  }
}
