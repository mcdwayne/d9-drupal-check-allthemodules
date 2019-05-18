<?php

namespace Drupal\mass_contact;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\mass_contact\Entity\MassContactCategoryInterface;

/**
 * The user opt out service.
 */
class OptOut implements OptOutInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * OptOut constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->entityManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptOutAccounts(array $categories = []) {
    // Get the latest configs.
    $config = $this->configFactory->get('mass_contact.settings');
    if ($config->get('optout_enabled') === MassContactInterface::OPT_OUT_DISABLED) {
      // Opt-out is completely disabled, return empty.
      return [];
    }

    $query = $this->entityManager->getStorage('user')->getQuery();
    $query->condition('status', 1);

    if ($config->get('optout_enabled') === MassContactInterface::OPT_OUT_GLOBAL) {
      // Any user with a value here has opted out.
      $query->condition(MassContactInterface::OPT_OUT_FIELD_ID, 0, '<>');
    }
    else {
      $category_ids = array_map(function (MassContactCategoryInterface $category) {
        return $category->id();
      }, $categories);
      $group = $query->orConditionGroup()
        // Opted out of one of the categories.
        ->condition(MassContactInterface::OPT_OUT_FIELD_ID, $category_ids, 'IN')
        // Or, has opted out globally.
        ->condition(MassContactInterface::OPT_OUT_FIELD_ID, '1');
      $query->condition($group);
    }

    return $query->execute();
  }

}
