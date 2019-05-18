<?php

namespace Drupal\drd\Plugin\Action;

use Drupal\drd\Entity\BaseInterface as RemoteEntityInterface;
use Drupal\drd\Entity\Core;

/**
 * Provides a 'DomainMove' action.
 *
 * @Action(
 *  id = "drd_action_domainmove",
 *  label = @Translation("Move domain to a different core"),
 *  type = "drd_domain",
 * )
 */
class DomainMove extends BaseEntity {

  /**
   * The destination core to which the domain should be moved.
   *
   * @var \Drupal\drd\Entity\CoreInterface
   */
  protected $core;

  /**
   * {@inheritdoc}
   */
  public function executeAction(RemoteEntityInterface $domain) {
    /** @var \Drupal\drd\Entity\DomainInterface $domain */

    if (!isset($this->core)) {
      $this->core = Core::load($this->arguments['dest-core-id']);
    }

    if (!$this->core) {
      $this->setOutput('Give core id is not valid.');
      return FALSE;
    }

    $domain
      ->setCore($this->core)
      ->save();

    $this->setOutput('Domain moved to core ' . $this->core->getName());
    return TRUE;
  }

}
