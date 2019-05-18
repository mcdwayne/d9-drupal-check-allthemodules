<?php

namespace Drupal\drd_pi;

use Drupal\drd\Entity\Core;

/**
 * Provides platform based core.
 */
class DrdPiCore extends DrdPiEntity {

  /**
   * List of domains.
   *
   * @var DrdPiDomain[]
   */
  protected $domains = [];

  /**
   * Host to which this core is attached.
   *
   * @var DrdPiHost
   */
  protected $host;

  /**
   * {@inheritdoc}
   */
  public function host() {
    return $this->host;
  }

  /**
   * Set the host to which this core is attached.
   *
   * @param DrdPiHost $host
   *   The host entity.
   *
   * @return $this
   */
  public function setHost(DrdPiHost $host) {
    $this->host = $host;
    return $this;
  }

  /**
   * Add a domain to this core.
   *
   * @param DrdPiDomain $domain
   *   The domain to be added.
   *
   * @return $this
   */
  public function addDomain(DrdPiDomain $domain) {
    $this->domains[$domain->id()] = $domain;
    return $this;
  }

  /**
   * Get all attached domains.
   *
   * @return DrdPiDomain[]
   *   List of domains.
   */
  public function getDomains() {
    return $this->domains;
  }

  /**
   * {@inheritdoc}
   */
  public function create() {
    $this->entity = Core::create([
      'name' => $this->label,
      'host' => $this->host()->getDrdEntity()->id(),
      'drupal_root' => '',
      'pi_type' => $this->account->getEntityTypeId(),
      'pi_account' => $this->account->id(),
      'pi_id_host' => $this->host()->id(),
      'pi_id_core' => $this->id,
    ]);
    $this->entity->save();
    return $this;
  }

}
