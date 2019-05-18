<?php

namespace Drupal\hidden_tab\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\hidden_tab\Entity\HiddenTabCredit;
use Drupal\hidden_tab\Event\HiddenTabPageFormEvent;
use Drupal\hidden_tab\Service\CreditCharging;

/**
 * To list all credit entities of a page, on it's edit form.
 */
class HiddenTabPageFormSubscriberForNewCredit extends ForNewEntityFormBase {

  /**
   * {@inherit}
   */
  protected $prefix = 'hidden_tab_add_new_credit_subscriber_0__';

  /**
   * {@inherit}
   */
  protected $currentlyTargetEntity = 'node';

  /**
   * {@inherit}
   */
  protected $e_type = 'hidden_tab_credit';

  /**
   * {@inherit}
   */
  protected $label;

  /**
   * To find the editing entity's credit entities.
   *
   * @var \Drupal\hidden_tab\Service\CreditCharging
   */
  protected $creditingService;

  /**
   * {@inheritdoc}
   */
  public function __construct(TranslationInterface $t,
                              MessengerInterface $messenger,
                              EntityTypeManagerInterface $entity_type_manager,
                              CreditCharging $credit_service) {
    parent::__construct($t, $messenger, $entity_type_manager);
    $this->creditingService = $credit_service;
    $this->label = t('Credit');
  }

  /**
   * {@inheritdoc}
   */
  protected function addForm(HiddenTabPageFormEvent $event): array {
    return HiddenTabCredit::littleForm($this->prefix, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function onValidate0(HiddenTabPageFormEvent $event) {
    HiddenTabCredit::validateForm($event->formState,
      $this->prefix,
      TRUE,
      'node',
      NULL);
  }

  /**
   * {@inheritdoc}
   */
  protected function onSave0(HiddenTabPageFormEvent $event): array {
    return HiddenTabCredit::extractFormValues($this->prefix, $event->formState, TRUE);
  }

}
