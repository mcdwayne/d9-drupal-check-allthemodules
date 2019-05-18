<?php

namespace Drupal\hidden_tab\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\hidden_tab\Entity\HiddenTabCreditListBuilder;
use Drupal\hidden_tab\Service\CreditCharging;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * To list all entities of the page, on it's edit form.
 */
class HiddenTabPageFormSubscriberForCreditList extends ForEntityListFormBase {

  /**
   * {@inheritdoc}
   */
  protected $prefix = 'hidden_tab_list_of_credits_form_subscriber_0__';

  /**
   * {@inheritdoc}
   */
  protected $entityType = 'hidden_tab_credit';

  /**
   * {@inheritdoc}
   */
  protected $label = 'Credits';

  /**
   * To pass to HiddenTabCreditInterface.
   *
   * @var \Drupal\hidden_tab\Service\CreditCharging
   */
  protected $cc;

  /**
   * {@inheritdoc}
   */
  public function __construct(TranslationInterface $t,
                              EntityTypeManagerInterface $em,
                              RequestStack $request_stack,
                              CreditCharging $cc) {
    parent::__construct($t, $em, $request_stack);
    $this->cc = $cc;
  }

  /**
   * {@inheritdoc}
   */
  protected function header(): array {
    return HiddenTabCreditListBuilder::header();
  }

  /**
   * {@inheritdoc}
   */
  protected function row(EntityInterface $entity): array {
    /** @noinspection PhpParamsInspection */
    return HiddenTabCreditListBuilder::row($this->cc, $entity);
  }

}
