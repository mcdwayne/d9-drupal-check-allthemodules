<?php

namespace Drupal\email_confirmer\ParamConverter;

use Symfony\Component\Routing\Route;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Email confirmation UUID param converter.
 */
class UuidConverter implements ParamConverterInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Construct an email confirmation UUID param converter.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $confirmation = NULL;
    if (Uuid::isValid($value)
      && $storage = $this->entityManager->getStorage('email_confirmer_confirmation')) {
      $entities = $storage->loadByProperties(['uuid' => $value]);
      $confirmation = ($entities) ? reset($entities) : NULL;
    }

    return $confirmation;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'email-confirmer-confirmation-uuid');
  }

}
