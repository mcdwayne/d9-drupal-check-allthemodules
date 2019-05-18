<?php

namespace Drupal\hidden_tab\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\hidden_tab\Entity\HiddenTabMailerListBuilder;

/**
 * To list all entities of the page, on it's edit form.
 */
class HiddenTabPageFormSubscriberForMailerList extends ForEntityListFormBase {

  /**
   * {@inheritdoc}
   */
  protected $prefix = 'hidden_tab_list_of_mailers_form_subscriber_0__';

  /**
   * {@inheritdoc}
   */
  protected $entityType = 'hidden_tab_mailer';

  /**
   * {@inheritdoc}
   */
  protected $label = 'Mailers';

  /**
   * {@inheritdoc}
   */
  protected function header(): array {
    return HiddenTabMailerListBuilder::header();
  }

  /**
   * {@inheritdoc}
   */
  protected function row(EntityInterface $entity): array {
    /** @noinspection PhpParamsInspection */
    return HiddenTabMailerListBuilder::row($entity);
  }

}
