<?php

namespace Drupal\commerce_user_points\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_store\Entity\Store;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Price;

/**
 * Provides the CommerceUserPoints.
 *
 * @CommerceCheckoutPane(
 *   id = "coupons",
 *   label = @Translation("Redeem Wallet Money"),
 *   default_step = "order_information",
 * )
 */
class CommerceUserPoints extends CheckoutPaneBase implements CheckoutPaneInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'single_coupon' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationSummary() {
    $summary = !empty($this->configuration['single_coupon']) ? $this->t('One time userpoints: Yes') : $this->t('One time userpoints: No');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['single_coupon'] = [
      '#type' => 'checkbox',
      '#attributes' => ['disabled' => 'disabled'],
      '#title' => $this->t('One time userpoints on Order?'),
      '#description' => $this->t('User can enter only one time userpoints on order.'),
      '#default_value' => $this->configuration['single_coupon'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['single_coupon'] = !empty($values['single_coupon']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {

    $arrNidPoints = $totalUsablePoints = [];

    $user = \Drupal::currentUser();

    $orderAdjustment = $this->order->getAdjustments();

    $flagPointsApplied = FALSE;

    foreach ($orderAdjustment as $adjustmentValue) {
      if ($adjustmentValue->getType() == 'custom') {
        $flagPointsApplied = TRUE;
      }
    }

    if (!$flagPointsApplied && !empty($user->id())) {
      // The options to display in our form radio buttons.
      $options = [
        '0' => t("Don't use points"),
        '1' => t('Use all usable points'),
        '2' => t('Use Specific points'),
      ];

      // Get all valid user points.
      $arrNidPoints = $this->calculateUsablePoints();

      $totalUsablePoints = round($arrNidPoints['total_usable_points']);

      $pane_form['user_points_redemption_type'] = [
        '#type' => 'radios',
        '#title' => t('User points Redeem'),
        '#options' => $options,
        '#description' => t('You have points at the moment') . " " . $totalUsablePoints,
        '#default_value' => '0',
      ];

      $pane_form['user_points_redemption'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Points'),
        '#default_value' => '',
        '#required' => FALSE,
      ];

      $pane_form['user_points_redemption']['#states'] = [
        'visible' => [
          ':input[name="coupons[user_points_redemption_type]"]' => ['value' => '2'],
        ],
      ];
    }

    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {

    $arrNidPoints = $totalUsablePoints = [];

    $values = $form_state->getValue($pane_form['#parents']);
    $thresholdValue = \Drupal::config('commerce_user_points.settings')->get('threshold_value');

    if (isset($values['user_points_redemption_type']) && !empty($values['user_points_redemption_type'])) {
      switch ($values['user_points_redemption_type']) {
        case '2':

          // Check if value is numeric.
          if (!is_numeric($values['user_points_redemption'])) {
            $form_state->setError($pane_form, $this->t('Please add numeric value for Points.'));
          }

          // Get all valid user points.
          $arrNidPoints = $this->calculateUsablePoints();
          $totalUsablePoints = round($arrNidPoints['total_usable_points']);

          if ($totalUsablePoints < $thresholdValue) {
            $translactedString = $this->t("Curently you have #totalUsablePoints point(s) in your account. You can utilize points after you reached to #thresholdValue points.");
            $translactedString = str_replace("#totalUsablePoints", $totalUsablePoints, $translactedString);
            $translactedString = str_replace("#thresholdValue", $thresholdValue, $translactedString);
            $form_state->setError($pane_form, $translactedString);
          }

          if ($this->order->hasItems()) {
            foreach ($this->order->getItems() as $orderItem) {
              $arrNidPoints = $orderItem->getTotalPrice();
              if ($values['user_points_redemption'] > $totalUsablePoints) {
                $translactedString = $this->t("You can maximum use #totalUsablePoints for points.");
                $translactedString = str_replace("#totalUsablePoints", number_format($totalUsablePoints), $translactedString);
                $form_state->setError($pane_form, $translactedString);
              }
            }
          }

          break;

        case '1':

          // Get all valid user points.
          $arrNidPoints = $this->calculateUsablePoints();
          $totalUsablePoints = round($arrNidPoints['total_usable_points']);
          $translactedString = $this->t("Curently you have #totalUsablePoints point(s) in your account. You can utilize points after you reached to #thresholdValue points.");
          $translactedString = str_replace("#totalUsablePoints", $totalUsablePoints, $translactedString);
          $translactedString = str_replace("#thresholdValue", $thresholdValue, $translactedString);
          if ($totalUsablePoints < $thresholdValue) {
            $form_state->setError($pane_form, $translactedString);
          }

          break;

        default:
          // code...
          break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $arrNidPoints = $totalUsablePoints = [];
    $values = $form_state->getValue($pane_form['#parents']);

    if (isset($values['user_points_redemption_type']) && !empty($values['user_points_redemption_type'])) {

      $arrNidPoints = $this->calculateUsablePoints();

      // Calculate usable points based on user selected value.
      switch ($values['user_points_redemption_type']) {
        case '2':
          $totalUsablePoints = $values['user_points_redemption'];
          break;

        case '1':
          // Get all valid user points.
          $totalUsablePoints = round($arrNidPoints['total_usable_points']);
          break;

        default:
          $totalUsablePoints = '0';
          break;
      }

      if (!empty($totalUsablePoints)) {

        $orderItemTotal = 0;

        if ($this->order->hasItems()) {
          foreach ($this->order->getItems() as $orderItem) {
            $orderItemTotal += $orderItem->getTotalPrice()->getNumber();
          }
        }

        if ($orderItemTotal < $totalUsablePoints) {
          $totalUsablePoints = $orderItemTotal;
        }

        foreach ($this->order->getItems() as $orderItem) {
          $purchasedEntity = $orderItem->getPurchasedEntity();
          $productId = $purchasedEntity->get('product_id')->getString();
          $product = Product::load($productId);
          // To get the store details for currency code.
          $store = Store::load(reset($product->getStoreIds()));
        }

        // Create adjustment object for current order.
        $adjustments = new Adjustment(
        [
          'type' => 'custom',
          'label' => 'User Points Deduction',
          'amount' => new Price('-' . $totalUsablePoints, $store->get('default_currency')->getString()),
        ]
        );

        $userPointsNids = $arrNidPoints['user_points_nids'];

        $deductUserPoints = $this->deductUserPoints($userPointsNids, $totalUsablePoints);

        if ($deductUserPoints) {
          // Add adjustment to order and save.
          $this->order->addAdjustment($adjustments);
          $this->order->save();
        }
      }
    }
  }

  /**
   * To deduct the point for the user.
   */
  public function deductUserPoints(array $userPointsNids, $totalUsablePoints) {

    $updatedPoints = 0;

    $calculatedRemainingPoints = $totalUsablePoints;

    // Get all valid user points.
    $nodes = entity_load_multiple('node', $userPointsNids);

    foreach ($nodes as $node) {

      $earnedNodePoints = $node->get('field_earned_points')->getString();
      $usedNodePoints = $node->get('field_used_points')->getString();
      $availableNodePoints = $earnedNodePoints - $usedNodePoints;

      $nextDeductPoints = $calculatedRemainingPoints - $availableNodePoints;

      if ($updatedPoints < $totalUsablePoints) {
        if ($nextDeductPoints > 0) {
          $updatedPoints += $availableNodePoints;
          $calculatedRemainingPoints = $nextDeductPoints;
          $nodeUpdatePoints = $usedNodePoints + $availableNodePoints;
          $node->set('field_used_points', $nodeUpdatePoints);
          $node->save();
        }
        else {
          $updatedPoints += $calculatedRemainingPoints;
          $nodeUpdatePoints = $usedNodePoints + $calculatedRemainingPoints;
          $node->set('field_used_points', $nodeUpdatePoints);
          $node->save();
        }
      }
    }

    return TRUE;
  }

  /**
   * Calculate user available points.
   *
   * @return array
   *   Return the array nid points.
   */
  public function calculateUsablePoints() {

    // Get all valid user points.
    $user = \Drupal::currentUser();

    $bundle = 'user_points';
    $query = \Drupal::entityQuery('node');
    $query->condition('status', 1);
    $query->condition('type', $bundle);
    $query->condition('uid', $user->id());
    $query->condition('field_point_status.value', '1', '=');
    $query->condition('field_validity_date.value', gmdate('Y-m-d'), '>=');
    $query->sort('field_validity_date.value', 'ASC');
    $entityIds = $query->execute();

    $nodes = entity_load_multiple('node', $entityIds);

    $totalEarnedPoints = 0;
    $totalUsedPoints = 0;
    $totalUsablePoints = 0;

    foreach ($nodes as $nodeObject) {
      $totalEarnedPoints += $nodeObject->get('field_earned_points')->getString();
      $totalUsedPoints += $nodeObject->get('field_used_points')->getString();
    }

    // Total usable points by logged in user.
    $totalUsablePoints = $totalEarnedPoints - $totalUsedPoints;

    $arrNidPoints = [];

    $arrNidPoints['total_usable_points'] = round($totalUsablePoints);
    $arrNidPoints['user_points_nids'] = $entityIds;

    return $arrNidPoints;
  }

}
