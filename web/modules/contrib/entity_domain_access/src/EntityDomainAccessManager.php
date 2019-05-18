<?php

namespace Drupal\entity_domain_access;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\domain_access\DomainAccessManager;
use Drupal\domain\DomainLoaderInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Checks the access status of entities based on domain settings.
 */
class EntityDomainAccessManager extends DomainAccessManager implements EntityDomainAccessManagerInterface {

  /**
   * The domain entity mapper.
   *
   * @var \Drupal\entity_domain_access\EntityDomainAccessMapper
   */
  protected $mapper;

  /**
   * Constructs a DomainAccessManager object.
   *
   * @param \Drupal\domain\DomainLoaderInterface $loader
   *   The domain loader.
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiator.
   * @param \Drupal\entity_domain_access\EntityDomainAccessMapper $mapper_service
   *   The domain entity mapper.
   */
  public function __construct(DomainLoaderInterface $loader, DomainNegotiatorInterface $negotiator, EntityDomainAccessMapper $mapper_service) {
    parent::__construct($loader, $negotiator);
    $this->mapper = $mapper_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultValue(FieldableEntityInterface $entity, FieldDefinitionInterface $definition) {
    $item = [];
    if (!\Drupal::service('entity_domain_access.mapper')->isDomainAccessEntityType($entity->getEntityType()->id())) {
      return $item;
    }

    $behavior = $definition->getThirdPartySetting('entity_domain_access', 'behavior', EntityDomainAccessMapper::BEHAVIOR_AUTO);

    $domains = [];

    if ($behavior == EntityDomainAccessMapper::BEHAVIOR_USER) {
      $domain_ids = $definition->getThirdPartySetting('entity_domain_access', 'domains', []);
      $domains = array_values(\Drupal::service('domain.loader')->loadMultiple($domain_ids));
    }
    else {
      $domains[] = \Drupal::service('domain.negotiator')
        ->getActiveDomain();
    }

    if ($entity->isNew()) {
      /** @var \Drupal\domain\DomainInterface $domain */
      foreach ($domains as $delta => $domain) {
        if (method_exists($domain, 'uuid')) {
          $item[$delta]['target_uuid'] = $domain->uuid();
        }
      }
    }
    // This code does not fire, but it should.
    else {
      foreach (static::getAccessValues($entity) as $id) {
        $item[] = $id;
      }
    }

    return $item;
  }

  /**
   * {@inheritdoc}
   */
  public function checkEntityHasDomains(EntityInterface $entity, array $domains) {
    $entity_domains = $this->getAccessValues($entity);

    $list = [];
    foreach ($domains as $domain_id) {
      if ($domain = $this->loader->load($domain_id)) {
        $list[$domain->id()] = $domain->getDomainId();
      }
    }

    return (bool) !empty(array_intersect($entity_domains, $list));
  }

  /**
   * {@inheritdoc}
   */
  public function checkEntityHasCurrentDomain(EntityInterface $entity) {
    if (!$current_domain = $this->negotiator->getActiveDomain()) {
      return FALSE;
    }
    return $this->checkEntityHasDomains($entity, [$current_domain->id()]);
  }

}
