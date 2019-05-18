<?php

namespace Drupal\role_paywall;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class RolePaywallManager.
 */
class RolePaywallManager implements RolePaywallManagerInterface {

  /** @var Array **/
  private $paywallNodes = [];

  /** @var \Drupal\Core\Config\ImmutableConfig **/
  private $configuration;

  /**
   * Constructs a new RolePaywallManager object.
   */
  public function __construct() {
    $this->configuration = \Drupal::config('role_paywall.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function addPaywallNode($id) {
    $this->paywallNodes[] = $id;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaywallNodes() {
    return $this->paywallNodes;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaywallRoles() {
    return array_values($this->configuration->get('roles') ?: []);
  }

  /**
   * {@inheritdoc}
   */
  public function getPaywallBundles() {
    return array_values($this->configuration->get('bundles') ?: []);
  }

  /**
   * {@inheritdoc}
   */
  public function getBarrier() {
    $barrier = $this->getRenderBlock($this->configuration->get('barrier_block'));
    \Drupal::moduleHandler()->alter('paywall_barrier', $barrier);
    return $barrier;
  }

  /**
   * Renders a block of the given id.
   *
   * @return \Drupal\Component\Render\MarkupInterface | NULL
   *   The block rendered
   */
  private function getRenderBlock($block_id) {
    if ($block_id) {
      $block = \Drupal\block\Entity\Block::load($block_id);
      $block_content = \Drupal::entityManager()->getViewBuilder('block')->view($block);
      return \Drupal::service('renderer')->renderRoot($block_content);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldAccessResult($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    $target_bundle = $field_definition->getTargetBundle();
    $field_name = $field_definition->getName();

    if ($items) {
      $entity = $items->getEntity();
      return $this->getAccessResult($operation, $field_name, $target_bundle, $account, $entity);
    }
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  public function getExtraFieldAccessResult($operation, $field_name, $target_bundle, $entity) {
    return $this->getAccessResult($operation, $field_name, $target_bundle, \Drupal::currentUser(), $entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessResult($operation, $field_name, $target_bundle, AccountInterface $account, EntityInterface $entity) {
    // Paywall not applying for uid 1.
    if ($account->id() === '1') {
      return AccessResult::neutral()->addCacheTags(['user:1']);
    }

    // @todo move it to specific module.
    $moduleHandler = \Drupal::service('module_handler');
    // 1-article-test support if module is enabled.
    if (!$account->isAnonymous() && $moduleHandler->moduleExists('role_paywall_article_test') && $moduleHandler->moduleExists('flag')) {
      if ($testFlagId = \Drupal::config('role_paywall_article_test.settings')->get('access_flag')) {
        $flagService = \Drupal::service('flag');
        $flag = $flagService->getFlagById($testFlagId);
        $flagged = $flagService->getFlagging($flag, $entity, $account);
        if (!is_null($flagged)) {
          $test_time = $flagged->get('created')->getValue()[0]['value'];
          $test_duration = (int) \Drupal::config('role_paywall_article_test.settings')->get('blocking_period_days');
          $test_ends = $test_time + $test_duration * 24 * 60 * 60;
          if ($test_ends > time()) {
            return AccessResult::neutral()->addCacheTags(['flagging:' . $flagged->getFlagId()]);
          }
        }
      }
    }

    $roles = $this->getPaywallRoles();
    $bundles = $this->getPaywallBundles();

    // Check if the paywall applies.
    if ($operation === 'view' && in_array($target_bundle, $bundles) && (empty(array_intersect($account->getRoles(), $roles)) || $account->isAnonymous())) {
      $activate_paywall_field = $this->configuration->get('activate_paywall_field')[$target_bundle];
      $premium_value = $entity->hasField($activate_paywall_field) ? $entity->get($activate_paywall_field)->getValue() : FALSE;
      // Check if its behind the paywall.
      if (!empty($premium_value) && $premium_value[0]['value'] === '1') {
        $hidden_fields = $this->configuration->get('hidden_fields')[$target_bundle];
        if (isset($hidden_fields[$field_name]) && $hidden_fields[$field_name] !== '0') {
          self::addPaywallNode($entity->id());
          return AccessResult::forbidden()->addCacheContexts(['user.roles'])->addCacheTags(['node:' . $entity->id()]);
        }
      }
    }

    return AccessResult::neutral();
  }

}
