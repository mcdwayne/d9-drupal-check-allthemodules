<?php

namespace Drupal\commerce_xero\Plugin\CommerceXero\processor;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_xero\Plugin\CommerceXero\CommerceXeroProcessorPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\TypedDataTrait;
use Drupal\xero\XeroQuery;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Assigns a Xero Tracking Category to the Xero Bank Transaction.
 *
 * This is an example processor plugin for basic functionality. A more robust
 * plugin may calculate the tracking category to use based on taxonomy or more
 * custom business logic.
 *
 * @CommerceXeroProcessor(
 *   id = "commerce_xero_tracking_category",
 *   label = @Translation("Adds Tracking Category"),
 *   types = {
 *     "xero_bank_transaction",
 *   },
 *   execution = "immediate",
 *   settings = {
 *     "tracking_category" = "",
 *     "tracking_option" = "",
 *   },
 *   required = FALSE
 * )
 */
class TrackingCategory extends CommerceXeroProcessorPluginBase implements ContainerFactoryPluginInterface {

  use TypedDataTrait;

  /**
   * Xero Query service.
   *
   * @var \Drupal\xero\XeroQuery
   */
  protected $query;

  /**
   * TrackingCategory constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\xero\XeroQuery $query
   *   The xero.query service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, XeroQuery $query) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->query = $query;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'settings' => [
        'tracking_category' => '',
        'tracking_option' => '',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();

    $default = $configuration['settings']['tracking_category'];

    $categories = $this->query->getCache('xero_tracking');
    $category_options = [];
    $options = [];

    if ($categories) {
      /* @var $category \Drupal\xero\Plugin\DataType\TrackingCategory */
      foreach ($categories as $i => $category) {
        if ($category->get('Status')->getValue() === 'ACTIVE') {
          $category_name = $category->get('Name')->getValue();
          $category_options[$category_name] = $category_name;

          if (!$default && count($category_options) === 1) {
            $default = reset($category_options);
          }

          if ($category_name === $default) {
            /* @var $option \Drupal\xero\Plugin\DataType\TrackingCategoryOption */
            foreach ($category->get('Options') as $n => $option) {
              if ($option->get('Status')->getValue() === 'ACTIVE') {
                $option_name = $option->get('Name')->getValue();
                $options[$option_name] = $option_name;
              }
            }
          }
          else {
            $options = ['_none' => $this->t('- Choose Tracking Category -')];
          }
        }
      }
    }

    $form['tracking_category'] = [
      '#type' => 'select',
      '#title' => $this->t('Tracking Category'),
      '#description' => $this->t('Choose the tracking category to use.'),
      '#default_value' => $configuration['settings']['tracking_category'],
      '#options' => $category_options,
      '#ajax' => [
        'callback' => [$this, 'onCategoryChange'],
        'wrapper' => 'tracking-option-wrapper',
        'effect' => 'fade',
      ],
      '#required' => TRUE,
    ];

    $form['tracking_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Tracking Option'),
      '#description' => $this->t('Choose the tracking option.'),
      '#default_value' => $configuration['settings']['tracking_option'],
      '#options' => $options,
      '#prefix' => '<div id="tracking-option-wrapper">',
      '#suffix' => '</div>',
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * AJAX callback on category change.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   *
   * @return array
   *   The form element.
   */
  public function onCategoryChange(array $form, FormStateInterface $formState) {
    return $form['processors']['commerce_xero_tracking_category']['settings']['tracking_option'];
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function process(PaymentInterface $paymentEntity, ComplexDataInterface $data) {
    $category = $this->configuration['settings']['tracking_category'];
    $option = $this->configuration['settings']['tracking_option'];

    // Do nothing if there is no category to set.
    if (empty($category) || empty($option)) {
      return TRUE;
    }

    // Find the LineItems for the data type.
    $definition = $data->getDataDefinition();
    if (!in_array($definition->getDataType(), $this->pluginDefinition['types'])) {
      return TRUE;
    }

    $tracking_item = [
      'Name' => $category,
      'Option' => $option,
    ];
    foreach ($data->get('LineItems') as $index => $line_item) {
      // Go through each line item and add the tracking category.
      $item = $line_item->getValue();
      $item['Tracking'] = [$tracking_item];
      $data->get('LineItems')->set($index, $item, TRUE);
    }

    return TRUE;
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
