<?php

namespace Drupal\drd\Command;

use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\drd\Plugin\Action\BaseEntityRemote;
use Drupal\drd\Plugin\Action\BaseHost as ActionBaseHost;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class BaseHost.
 *
 * @package Drupal\drd
 */
abstract class BaseHost extends BaseEntity {

  /**
   * {@inheritdoc}
   */
  protected function getEntities(InputInterface $input, DrupalStyle $io, BaseEntityRemote $action) {
    if (!($action instanceof ActionBaseHost)) {
      return NULL;
    }

    return $this->getService($input)
      ->hosts();
  }

}
