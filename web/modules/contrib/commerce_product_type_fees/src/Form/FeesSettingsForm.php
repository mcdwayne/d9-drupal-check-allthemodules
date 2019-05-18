<?php

namespace Drupal\commerce_product_type_fees\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\commerce_order\AdjustmentTypeManager;

/**
 * Configure fee.
 */
class FeesSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;


  /**
   * The UUID generator.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidGenerator;

  /**
   * The adjustment type manager.
   *
   * @var \Drupal\commerce_order\AdjustmentTypeManager
   */
  protected $adjustmentTypeManager;

  /**
   * Constructs a SiteInformationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_generator
   *   The UUID generator.
   * @param \Drupal\commerce_order\AdjustmentTypeManager $adjustment_type_manager
   *   The adjustment type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, UuidInterface $uuid_generator, AdjustmentTypeManager $adjustment_type_manager) {
    parent::__construct($config_factory);

    $this->entityTypeManager = $entity_type_manager;
    $this->uuidGenerator = $uuid_generator;
    $this->adjustmentTypeManager = $adjustment_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('uuid'),
      $container->get('plugin.manager.commerce_adjustment_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_product_type_fees_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_product_type_fees.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_product_type_fees.settings');
    foreach ($this->getProductTypes() as $type) {
      $form[$type->id()] = [
        '#type' => 'details',
        '#title' => $type->label(),
        '#open' => TRUE,
      ];
      $wrapper_id = Html::getUniqueId('fee-type-ajax-wrapper');

      // Ajax callbacks need fees to be in form state.
      if (!$form_state->get($type->id() . '_fees_form_initialized')) {
        $fees = $config->get($type->id() . '_fees');
        $form_state->set($type->id() . '_fees', $fees);
        $form_state->set($type->id() . '_fees_form_initialized', TRUE);
      }

      $form[$type->id()][$type->id() . '_fees'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Fee rate'),
          $this->t('Percentage'),
          $this->t('Operations'),
        ],
        '#input' => FALSE,
      ];

      $form[$type->id()][$type->id() . '_fees']['#prefix'] = '<div id="' . $wrapper_id . '">';
      $form[$type->id()][$type->id() . '_fees']['#suffix'] = '</div>';

      foreach ($form_state->get($type->id() . '_fees') as $index => $fee) {
        if (isset($fee)) {
          $fee_form = &$form[$type->id()][$type->id() . '_fees'][$index];
          $fee_form['fee']['id'] = [
            '#type' => 'value',
            '#value' => $fee['fee']['id'] ? $fee['fee']['id'] : $this->uuidGenerator->generate(),
          ];
          $fee_form['fee']['label'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Name'),
            '#default_value' => $fee['fee']['label'] ? $fee['fee']['label'] : '',
            '#maxlength' => 255,
          ];
          $fee_form['percentage'] = [
            '#type' => 'commerce_number',
            '#title' => $this->t('Percentage'),
            '#default_value' => $fee['percentage'] ? $fee['percentage'] : 0,
            '#field_suffix' => $this->t('%'),
            '#min' => 0,
            '#max' => 100,
          ];
          $fee_form['remove'] = [
            '#type' => 'submit',
            '#name' => 'remove_fee_' . $type->id() . $index,
            '#value' => $this->t('Remove'),
            '#limit_validation_errors' => [],
            '#submit' => [[get_class($this), 'removeFeeSubmit']],
            '#fee_index' => $index,
            '#fee_type' => $type->id(),
            '#ajax' => [
              'callback' => [get_class($this), 'ajaxCallback'],
              'wrapper' => $wrapper_id,
            ],
          ];
        }
      }

      $form[$type->id()][$type->id() . '_fees'][] = [
        'add_fee' => [
          '#type' => 'submit',
          '#name' => 'add_fee_' . $type->id(),
          '#value' => $this->t('Add fee'),
          '#submit' => [[get_class($this), 'addFeeSubmit']],
          '#fee_type' => $type->id(),
          '#limit_validation_errors' => [],
          '#ajax' => [
            'callback' => [get_class($this), 'ajaxCallback'],
            'wrapper' => $wrapper_id,
          ],
        ],
      ];

    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Ajax callback for fee operations.
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state) {
    $type = $form_state->getTriggeringElement()['#fee_type'];
    return $form[$type][$type . '_fees'];
  }

  /**
   * Submit callback for adding a new fees.
   */
  public static function addFeeSubmit(array $form, FormStateInterface $form_state) {
    $type = $form_state->getTriggeringElement()['#fee_type'];
    $fees = $form_state->get($type . '_fees');
    $fees[] = [];
    $form_state->set($type . '_fees', $fees);
    $form_state->setRebuild();
  }

  /**
   * Submit callback for removing a fees.
   */
  public static function removeFeeSubmit(array $form, FormStateInterface $form_state) {
    $type = $form_state->getTriggeringElement()['#fee_type'];
    $fees = $form_state->get($type . '_fees');
    $index = $form_state->getTriggeringElement()['#fee_index'];
    unset($fees[$index]);
    $form_state->set($type . '_fees', $fees);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->getProductTypes() as $type) {
      $fees = $form_state->getValue($type->id() . '_fees');
      foreach ($fees as $index => $fee) {
        if (isset($fee['fee']['id']) && !empty($fee['fee']['id'])) {
          // Check that fee name and percentage field should not be empty.
          if (isset($fees[$index]['fee']['label']) && empty($fees[$index]['fee']['label'])) {
            $err_field_name = $type->id() . '_fees][' . $index . '][fee][label';
            $form_state->setErrorByName($err_field_name, $this->t("Fee name can't be empty."));
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $conf = $this->configFactory->getEditable('commerce_product_type_fees.settings');
    foreach ($this->getProductTypes() as $type) {
      $fees = [];
      foreach ($form_state->getValue($type->id() . '_fees') as $fee) {
        if (isset($fee['fee']['id']) && !empty($fee['fee']['id'])) {
          $fees[] = $fee;
        }
      }
      $conf->set($type->id() . '_fees', $fees);
    }

    $conf->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Get all product types.
   *
   * @return array
   *   Returns available product types
   */
  public function getProductTypes() {
    return $this->entityTypeManager->getStorage('commerce_product_type')->loadMultiple();
  }

}
