<?php

namespace Drupal\drd_pi;

use Drupal\drd\Entity\Host;

/**
 * Provides platform based host.
 */
class DrdPiHost extends DrdPiEntity {

  /**
   * {@inheritdoc}
   */
  public function host() {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function create() {
    $this->entity = Host::create([
      'name' => $this->label,
      'pi_type' => $this->account->getEntityTypeId(),
      'pi_account' => $this->account->id(),
      'pi_id_host' => $this->id,
    ]);
    $this->entity->save();
    return $this;
  }

}
