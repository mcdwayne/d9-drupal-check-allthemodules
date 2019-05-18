<?php

namespace Drupal\commerce_installments\Plugin\Commerce\CheckoutFlow;

use Drupal\commerce_checkout\CheckoutPaneManager;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowWithPanesBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a default installments checkout flow.
 *
 * @CommerceCheckoutFlow(
 *   id = "commerce_installments_default",
 *   label = "Commerce Installments - Default",
 * )
 */
class InstallmentsDefault extends CheckoutFlowWithPanesBase {


  /**
   * The installment plan method.
   *
   * @var \Drupal\commerce_installments\Entity\InstallmentPlanMethodInterface
   */
  protected $installmentPlanMethodStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $pane_id, $pane_definition, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, RouteMatchInterface $route_match, CheckoutPaneManager $pane_manager) {
    parent::__construct($configuration, $pane_id, $pane_definition, $entity_type_manager, $event_dispatcher, $route_match, $pane_manager);

    $this->installmentPlanMethodStorage = $this->entityTypeManager->getStorage('installment_plan_method');
  }

  /**
   * {@inheritdoc}
   */
  public function getSteps() {
    // Note that previous_label and next_label are not the labels
    // shown on the step itself. Instead, they are the labels shown
    // when going back to the step, or proceeding to the step.
    $steps = [
      'login' => [
        'label' => $this->t('Login'),
        'previous_label' => $this->t('Go back'),
        'has_sidebar' => FALSE,
      ],
      'order_information' => [
        'label' => $this->t('Order information'),
        'has_sidebar' => TRUE,
        'previous_label' => $this->t('Go back'),
      ],
      'installments' => [
        'label' => $this->t('Installments'),
        'next_label' => $this->t('Continue to Installment Plan'),
        'previous_label' => $this->t('Go back'),
        'has_sidebar' => FALSE,
      ],
      'review' => [
        'label' => $this->t('Review'),
        'next_label' => $this->t('Continue to Review'),
        'previous_label' => $this->t('Go back'),
        'has_sidebar' => TRUE,
      ],
    ] + parent::getSteps();

    // Remove installment plan step if not eligible.
    if (!$this->installmentPlanMethodStorage->loadEligible($this->order)) {
      unset($steps['installments']);
    }

    return $steps;
  }

}
