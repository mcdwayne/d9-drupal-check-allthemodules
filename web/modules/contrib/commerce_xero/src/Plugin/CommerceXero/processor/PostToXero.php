<?php

namespace Drupal\commerce_xero\Plugin\CommerceXero\processor;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_xero\Plugin\CommerceXero\CommerceXeroProcessorPluginBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\TypedDataTrait;
use Drupal\xero\XeroQuery;
use Drupal\xero\TypedData\XeroTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Posts the data to Xero.
 *
 * @CommerceXeroProcessor(
 *   id = "commerce_xero_send",
 *   label = @Translation("Posts to Xero"),
 *   types = {},
 *   execution = "send",
 *   settings = {},
 *   required = TRUE
 * )
 */
class PostToXero extends CommerceXeroProcessorPluginBase implements ContainerFactoryPluginInterface {

  use TypedDataTrait;

  /**
   * Xero Query service.
   *
   * @var \Drupal\xero\XeroQuery
   */
  protected $query;

  /**
   * Initialize method.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\xero\XeroQuery $query
   *   The xero query.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, XeroQuery $query) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->query = $query;
    $this->typedDataManager = $this->getTypedDataManager();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process(PaymentInterface $payment, ComplexDataInterface $data) {
    if ($data instanceof XeroTypeInterface) {
      $definition = $data->getDataDefinition();
      $listDefinition = $this->typedDataManager->createListDataDefinition($definition->getDataType());
      /** @var \Drupal\Core\TypedData\Plugin\DataType\ItemList $list */
      $list = $this->typedDataManager->create($listDefinition, [$data->getValue()]);

      /** @var \Drupal\xero\TypedData\XeroTypeInterface $data */
      $this->query
        ->setType($definition->getDataType())
        ->setFormat('xml')
        ->setMethod('post')
        ->setData($list);

      $result = $this->query->execute();

      if ($result !== FALSE) {
        // Sets the xero reference on the payment. This should always return
        // true unless overridden.
        $hasField = $payment->getFieldDefinition('xero_transaction');
        if ($hasField !== NULL) {
          /** @var \Drupal\xero\TypedData\XeroTypeInterface $item */
          $item = $result->get(0);
          $guid = $item->get($item->getGUIDName())->getValue();

          $values = [
            'guid' => $guid,
            'label' => $guid,
            'type' => $item->getDataDefinition()->getDataType(),
          ];

          if ($payment->get('xero_transaction')->count() > 0) {
            $payment->get('xero_transaction')->appendItem($values);
          }
          else {
            $payment->set('xero_transaction', $values, TRUE);
          }

          try {
            $payment->save();
          }
          catch (EntityStorageException $e) {
            return FALSE;
          }
        }
      }
      else {
        return FALSE;
      }

      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('xero.query')
    );
  }

}
