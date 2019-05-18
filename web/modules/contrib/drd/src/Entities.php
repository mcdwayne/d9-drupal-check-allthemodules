<?php

namespace Drupal\drd;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\drd\Entity\Core;
use Drupal\drd\Entity\Domain;
use Drupal\drd\Entity\Host;

/**
 * Query for DRD entities.
 */
class Entities implements EntitiesInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * DRD entity type id.
   *
   * @var string
   */
  protected $type;

  /**
   * Properties for selection.
   *
   * @var array
   */
  protected $properties = [];

  /**
   * DRD host entity id.
   *
   * @var int
   */
  protected $hostId;

  /**
   * DRD core entity id.
   *
   * @var int
   */
  protected $coreId;

  /**
   * DRD domain entity id.
   *
   * @var int
   */
  protected $domainId;

  /**
   * Tag id.
   *
   * @var int
   */
  protected $tagId;

  /**
   * Construct the Entity object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectionCriteria() {
    $result = [];
    foreach ([
      'tag' => $this->tagId,
      'host-id' => $this->hostId,
      'core-id' => $this->coreId,
      'domain-id' => $this->domainId,
    ] as $key => $value) {
      if (!empty($value)) {
        $result[$key] = $value;
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function setTag($name) {
    if (!empty($name)) {
      $terms = taxonomy_term_load_multiple_by_name($name);
      if (!empty($terms)) {
        $this->tagId = reset($terms)->id();
      }
      else {
        $this->tagId = -1;
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setHost($name) {
    if (!empty($name)) {
      $hosts = \Drupal::entityTypeManager()->getStorage('drd_host')
        ->loadByProperties([
          'name' => $name,
        ]);
      if (!empty($hosts)) {
        $this->hostId = reset($hosts)->id();
      }
      else {
        $this->hostId = -1;
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setHostId($id) {
    if (!empty($id)) {
      $this->hostId = $id;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCore($name) {
    if (!empty($name)) {
      $cores = \Drupal::entityTypeManager()->getStorage('drd_core')
        ->loadByProperties([
          'name' => $name,
        ]);
      if (!empty($cores)) {
        $this->coreId = reset($cores)->id();
      }
      else {
        $this->coreId = -1;
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCoreId($id) {
    if (!empty($id)) {
      $this->coreId = $id;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDomain($domain) {
    if (!empty($domain)) {
      $domains = \Drupal::entityTypeManager()->getStorage('drd_domain')
        ->loadByProperties([
          'domain' => $domain,
        ]);
      if (!empty($domains)) {
        $this->domainId = reset($domains)->id();
      }
      else {
        $this->domainId = -1;
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDomainId($id) {
    if (!empty($id)) {
      $this->domainId = $id;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hosts() {
    $this->type = 'host';
    $this->properties = [
      'status' => 1,
    ];
    if ($this->domainId) {
      /* @var \Drupal\drd\Entity\DomainInterface $domain */
      if ($domain = Domain::load($this->domainId)) {
        $this->coreId = $domain->getCore()->id();
      }
      else {
        return $this->none();
      }
    }
    if ($this->coreId) {
      /* @var \Drupal\drd\Entity\CoreInterface $core */
      if ($core = Core::load($this->coreId)) {
        $this->hostId = $core->getHost()->id();
      }
      else {
        return $this->none();
      }
    }
    if ($this->hostId) {
      return $this->search($this->hostId);
    }
    return $this->search($this->hostIdsByTerm());
  }

  /**
   * {@inheritdoc}
   */
  public function cores() {
    $this->type = 'core';
    $this->properties = [
      'status' => 1,
    ];
    if ($this->hostId) {
      $this->properties['host'] = $this->hostId;
    }
    else {
      if ($this->domainId) {
        /* @var \Drupal\drd\Entity\DomainInterface $domain */
        if ($domain = Domain::load($this->domainId)) {
          $this->coreId = $domain->getCore()->id();
        }
        else {
          return $this->none();
        }
      }
      if ($this->coreId) {
        return $this->search($this->coreId);
      }
    }
    return $this->search($this->coreIdsByTerm());
  }

  /**
   * {@inheritdoc}
   */
  public function domains() {
    $this->type = 'domain';
    $this->properties = [
      'status' => 1,
      'installed' => 1,
    ];
    if ($this->hostId) {
      if ($host = Host::load($this->hostId)) {
        array_walk($host->getCores(), function (Core $core) {
          $this->properties['core'][] = $core->id();
        });
      }
      else {
        return $this->none();
      }
    }
    elseif ($this->coreId) {
      $this->properties['core'] = $this->coreId;
    }
    elseif ($this->domainId) {
      return $this->search($this->domainId);
    }
    return $this->search($this->domainIdsByTerm());
  }

  /**
   * Get a list of all host IDs by a taxonomy term.
   *
   * @return int[]|bool
   *   Array of host IDs or FALSE;
   */
  private function hostIdsByTerm() {
    return $this->entityIdsByTerm('h');
  }

  /**
   * Get a list of all core IDs by a taxonomy term.
   *
   * @return int[]|bool
   *   Array of core IDs or FALSE;
   */
  private function coreIdsByTerm() {
    return $this->entityIdsByTerm('c');
  }

  /**
   * Get a list of all domain IDs by a taxonomy term.
   *
   * @return int[]|bool
   *   Array of domain IDs or FALSE;
   */
  private function domainIdsByTerm() {
    return $this->entityIdsByTerm('d');
  }

  /**
   * Get a list of entity IDs by a taxonomy term.
   *
   * @param string $alias
   *   The table alias c|h|d for host|core|domain.
   *
   * @return int[]|bool
   *   Array of entity IDs or FALSE;
   */
  private function entityIdsByTerm($alias) {
    if ($this->tagId) {
      $query = \Drupal::database()->select('drd_domain', 'd');
      $query->join('drd_core', 'c', 'd.core = c.id');
      $query->join('drd_host', 'h', 'c.host = h.id');
      $query->leftJoin('drd_domain__terms', 'dt', 'd.id = dt.entity_id');
      $query->leftJoin('drd_core__terms', 'ct', 'c.id = ct.entity_id');
      $query->leftJoin('drd_host__terms', 'ht', 'h.id = ht.entity_id');
      $ids = $query->orConditionGroup()
        ->condition('dt.terms_target_id', $this->tagId)
        ->condition('ct.terms_target_id', $this->tagId)
        ->condition('ht.terms_target_id', $this->tagId);
      $query
        ->fields($alias, ['id'])
        ->condition($ids);
      return $query
        ->execute()
        ->fetchCol();
    }
    return FALSE;
  }

  /**
   * Nothing found, output a message and return FALSE.
   *
   * @return bool
   *   Retrun FALSE if no entity was found.
   */
  protected function none() {
    drupal_set_message('No ' . $this->type . ' found!', 'error');
    return FALSE;
  }

  /**
   * One entity found.
   *
   * @param int $id
   *   The id of the found entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Array containing one entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function oneEntity($id) {
    $entity = $this->entityTypeManager
      ->getStorage('drd_' . $this->type)
      ->load($id);
    return empty($entity) ? [] : [$entity];

  }

  /**
   * Multiple entities found.
   *
   * @param int $id
   *   ID of the entity to search or NULL to find all.
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface[]
   *   List of all found entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function search($id) {
    if (is_scalar($id) && !empty($id)) {
      $entities = $this->oneEntity($id);
    }
    elseif (is_array($id) && empty($id)) {
      $entities = [];
    }
    else {
      if (is_array($id)) {
        $this->properties['id'] = $id;
      }
      $entities = $this->entityTypeManager
        ->getStorage('drd_' . $this->type)
        ->loadByProperties($this->properties);
    }

    if (empty($entities)) {
      return $this->none();
    }

    return $entities;
  }

}
