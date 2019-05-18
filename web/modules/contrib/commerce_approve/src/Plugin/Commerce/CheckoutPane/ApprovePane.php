<?php

namespace Drupal\commerce_approve\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_product\Entity\Product;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides pane for checking off items.
 *
 * @CommerceCheckoutPane(
 *   id = "approve",
 *   label = @Translation("Order item approve"),
 *   display_label = @Translation("Item Approve"),
 *   default_step = "review",
 *   wrapper_element = "container",
 * )
 */
class ApprovePane extends CheckoutPaneBase {

  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, CheckoutFlowInterface $checkout_flow, $entity_type_manager, ConfigFactory $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow, $entity_type_manager);
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $checkout_flow,
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationSummary() {
    return $this->t('Pane title: @title', ['@title' => $this->configuration['pane_title']]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    parent::buildConfigurationForm($form, $form_state);
    $form['pane_title'] = [
      '#type' => 'textfield',
      '#default_value' => $this->configuration['pane_title'],
      '#title' => 'Checkout pane title',
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
      $this->configuration['pane_title'] = $values['pane_title'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $order_items = $this->order->getItems();
    $config = $this->configFactory->getEditable('commerce_approve.settings');
    $pane_form['#title'] = $this->configuration['pane_title'];

    /** @var \Drupal\commerce_order\Entity\OrderItem $item */
    foreach ($order_items as $item) {
      /** @var \Drupal\commerce_product\Entity\Product $order_item */
      $order_item = $item->getPurchasedEntity()->getProduct();
      $required = $item->getData('approved') ? FALSE : $this->requiresCheckOff($order_item);
      if (!$required) {
        continue;
      }
      if ($required) {
        $pane_form[0]['approve_' . $item->id()] = [
          '#prefix' => '<h4>' . $item->label() . '</h4>',
          '#type'  => 'checkbox',
          '#title' => $required['text'] ?? t('I have verified this product is correct'),
          '#required' => TRUE,
        ];
      }
    }

    // If it's been enabled we want to show the text, but only if it's both
    // enabled and filled out.
    if (isset($pane_form[0])) {
      $pane_form['about'] = [
        '#markup' => $config->get('about_text'),
        '#access' => $config->get('about_enabled') && $config->get('about_text'),
        '#weight' => -10,
      ];
    }

    return $pane_form;
  }

  /**
   * Finds fields referencing terms and check if it requires an approval.
   *
   * @param \Drupal\commerce_product\Entity\Product $order_item
   *   Order item to check.
   *
   * @return bool|array
   *   Array with values if it requires manual approval, FALSE otherwise.
   */
  public function requiresCheckOff(Product $order_item) {
    foreach ($order_item->referencedEntities() as $referencedEntity) {
      $class = get_class($referencedEntity);
      if (strpos($class, 'Term') > -1) {
        if ($referencedEntity->hasField('field_require_approval')) {
          $lock = $referencedEntity->get('field_require_approval');
          if ($lock->getValue()[0]['value'] == TRUE) {
            return [
              'lock' => TRUE,
              'text' => $referencedEntity->get('field_require_approval_text')->getValue()[0]['value'],
            ];
          }
        }
      }
    }
    return FALSE;
  }

}
