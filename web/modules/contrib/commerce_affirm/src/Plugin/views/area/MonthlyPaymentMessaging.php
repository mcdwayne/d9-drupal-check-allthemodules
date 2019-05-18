<?php

namespace Drupal\commerce_affirm\Plugin\views\area;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\commerce_affirm\MinorUnitsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an Affirm area handler for the order total.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("commerce_affirm_monthly_payment_messaging")
 */
class MonthlyPaymentMessaging extends AreaPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The order storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  protected $orderStorage;

  /**
   * The product variation storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  protected $productVariationStorage;

  /**
   * The minor units converter service.
   *
   * @var \Drupal\commerce_affirm\MinorUnitsInterface
   */
  protected $minorUnits;

  /**
   * Constructs a new OrderTotal instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_affirm\MinorUnitsInterface $minor_units
   *   The currency minor units service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, MinorUnitsInterface $minor_units) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->orderStorage = $entity_type_manager->getStorage('commerce_order');
    $this->productStorage = $entity_type_manager->getStorage('commerce_product_variation');
    $this->minorUnits = $minor_units;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('commerce_affirm.minor_units')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['page_type'] = ['default' => 'cart'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['empty']['#description'] = $this->t("Even if selected, this area handler will never render if a valid order cannot be found in the View's arguments.");
    $form['page_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Page type'),
      '#default_value' => $this->options['page_type'],
      '#options' => _commerce_affirm_page_types(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if (!$empty || !empty($this->options['empty'])) {
      $price = NULL;
      $variation = NULL;
      foreach ($this->view->argument as $argument) {
        // First look for an order_id argument.
        if (!$argument instanceof NumericArgument) {
          continue;
        }
        switch ($argument->getField()) {
          case 'commerce_order.order_id':
          case 'commerce_order_item.order_id':
            /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
            $order = $this->orderStorage->load($argument->getValue());
            $price = $order->getTotalPrice();
            break;

          case 'commerce_product_variation.id':
            /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
            $variation = $this->productVariationStorage->load($argument->getValue());
            $price = $variation->getPrice();
            break;

          default:
            continue 2;
        }
      }
      if ($price) {
        $number = $this->minorUnits->toMinorUnits($price);
        return [
          '#theme' => 'commerce_affirm_monthly_payment_message',
          '#page_type' => $this->options['page_type'],
          '#number' => $number,
          '#variation' => $variation,
        ];
      }
    }
    return [];
  }

}
