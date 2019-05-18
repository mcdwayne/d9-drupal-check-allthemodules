<?php
namespace Drupal\commerce_admin_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowWithPanesBase;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the contact information pane.
 *
 * @CommerceCheckoutPane(
 *   id = "commerce_admin_checkout_order_items",
 *   label = @Translation("Order Items"),
 *   default_step = "order_information",
 *   wrapper_element = "fieldset",
 * )
 */
class AdminOrderItemsPane extends CheckoutPaneBase implements CheckoutPaneInterface {

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow = NULL) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
      $checkout_flow
    );
    $instance->setCurrentUser($container->get('current_user'));
    return $instance;
  }

  /**
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   * 
   * @return $this
   */
  public function setCurrentUser(AccountProxyInterface $currentUser) {
    $this->currentUser = $currentUser;
  }

  /**
   * @inheritDoc
   */
  public function isVisible() {
    return $this->currentUser->hasPermission('edit cart items during checkout');
  }


  /**
   * @inheritDoc
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form['form'] = [
      '#type' => 'commerce_admin_checkout_order_items_form',
      '#order_id' => $this->order->id(),
      '#element_ajax' => [
        [CheckoutFlowWithPanesBase::class, 'ajaxRefreshPanes'],
      ],
    ];
    return $pane_form;
    
  }

}
