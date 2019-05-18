<?php

namespace Drupal\drd\Command;

/**
 * Class UpdateTranslations.
 *
 * @package Drupal\drd
 */
class UpdateTranslations extends BaseDomain {

  /**
   * Construct the UpdateTranslations command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_update_translations';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:l10n:update')
      ->setDescription($this->trans('commands.drd.action.l10n.update.description'));
  }

}
