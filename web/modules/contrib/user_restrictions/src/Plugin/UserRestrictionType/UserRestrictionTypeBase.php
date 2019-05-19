<?php

namespace Drupal\user_restrictions\Plugin\UserRestrictionType;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user_restrictions\Entity\UserRestrictions;
use Drupal\user_restrictions\Plugin\UserRestrictionTypeInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class UserRestrictionTypeBase extends PluginBase implements UserRestrictionTypeInterface {

  use StringTranslationTrait;

  /**
   * The entity storage interfacce.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * The logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * List of patterns for the restriction type.
   *
   * @var string[]
   */
  protected $patterns = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_manager, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityStorage = $entity_manager->getStorage('user_restrictions');
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('entity_type.manager'), $container->get('logger.channel.user_restrictions'));
  }

  /**
   * Check if the specified value matches the restriction.
   *
   * @param string $value
   *   String to check against all restrictions of the type.
   *
   * @return bool|\Drupal\user_restrictions\Entity\UserRestrictions
   *   The restriction entity if the value matches one of the restrictions,
   *   FALSE otherwise.
   */
  protected function matchesValue($value) {
    // Load rules with exact pattern matches.
    $query = $this->entityStorage->getQuery();
    $query->condition('rule_type', $this->getPluginId())
      ->condition('pattern', $value)
      ->condition('expiry', REQUEST_TIME, '>');
    $results = $query->execute();
    $exact_rules = $this->entityStorage->loadMultiple($results);

    if (!empty($exact_rules)) {
      // Simply take the first matching rule as we have no weight (yet).
      /** @var \Drupal\user_restrictions\Entity\UserRestrictions $rule */
      $rule = reset($exact_rules);
      return ($rule->getAccessType() === UserRestrictions::BLACKLIST) ? $rule : FALSE;
    }

    // Load all rules of the restriction type.
    $query = $this->entityStorage->getQuery();
    $query->condition('rule_type', $this->getPluginId())
      ->condition('expiry', REQUEST_TIME, '>');
    $results = $query->execute();
    $rules = $this->entityStorage->loadMultiple($results);

    if (empty($rules)) {
      return FALSE;
    }

    // Set the return variable to FALSE to allow by default.
    $return = FALSE;
    /** @var \Drupal\user_restrictions\Entity\UserRestrictions $rule */
    foreach ($rules as $rule) {
      if (preg_match('/' . $rule->getPattern() . '/i', $value)) {
        // Exit loop after first whitelisted pattern.
        if ($rule->getAccessType() === UserRestrictions::WHITELIST) {
          return FALSE;
        }
        elseif ($rule->getAccessType() === UserRestrictions::BLACKLIST) {
          // If a matching pattern is blacklisted store it but don't return
          // as there may be a whitelisted pattern further in the loop.
          $return = $rule;
        }
      }
    }

    // Return either no match or the blacklisted rule.
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getPatterns() {
    if (!empty($this->patterns)) {
      return $this->patterns;
    }
    $rules = $this->entityStorage
      ->loadByProperties(['rule_type' => $this->getPluginId()]);
    if (empty($rules)) {
      return [];
    }
    /** @var \Drupal\user_restrictions\Entity\UserRestrictions $rule */
    foreach ($rules as $id => $rule) {
      $this->patterns[$id] = $rule->getPattern();
    }
    return $this->patterns;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getErrorMessage() {
    return $this->t('Using reserved data.');
  }

}
