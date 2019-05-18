<?php

namespace Drupal\commerce_rental\Form;

use Drupal\commerce\EntityTraitManager;
use Drupal\commerce\Form\CommerceBundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_rental\PeriodCalculatorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RentalPeriodTypeForm extends CommerceBundleEntityFormBase {

  /**
   * The period calculator manager
   *
   * @var \Drupal\commerce_rental\PeriodCalculatorManager
   */
  protected $periodCalculatorManager;

  /**
   * Constructs a new RentalPeriodTypeForm object.
   *
   * @param \Drupal\commerce\EntityTraitManagerInterface $trait_manager
   *   The entity trait manager.
   * @param \Drupal\commerce_rental\RentalAttributeFieldManagerInterface $attribute_field_manager
   *   The attribute field manager.
   */
  public function __construct(EntityTraitManager $trait_manager, PeriodCalculatorManager $period_calculator_manager) {
    parent::__construct($trait_manager);

    $this->periodCalculatorManager = $period_calculator_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.commerce_entity_trait'),
      $container->get('plugin.manager.period_calculator')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $period_type = $this->entity;
    $content_entity_id = $period_type->getEntityType()->getBundleOf();

    $calculator_options = [];
    foreach ($period_type->getCalculatorTypesList() as $key => $value) {
      $calculator_options[$key] = $key;
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $period_type->label(),
      '#description' => $this->t("Label for the %content_entity_id entity type (bundle).", ['%content_entity_id' => $content_entity_id]),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $period_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_rental\Entity\RentalPeriod::load',
      ],
      '#disabled' => !$period_type->isNew(),
    ];

    $form['calculator'] = [
      '#type' => 'select',
      '#title' => $this->t('Calculator'),
      '#options' => $calculator_options,
      '#default_value' => $period_type->getCalculatorId(),
      '#required' => TRUE,
    ];

    // TODO: Automatically disable and enable traits based on selected calculator requirements
    $form = $this->buildTraitForm($form, $form_state);

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $calculator = $this->periodCalculatorManager->getDefinition($form_state->getValue('calculator'));
    $available_traits = $form_state->getValue('traits');
    $required_traits = $calculator['traits'];
    foreach ($required_traits as $required_trait) {
      if (in_array($required_trait, $available_traits) && $available_traits[$required_trait] == FALSE) {
        $form_state->setErrorByName('traits][#options][' . $required_trait,
          t('The %calculator calculator plugin requires the %trait trait, please enable it.', [
            '%calculator' => $calculator['name'],
            '%trait' => $form['traits']['#options'][$required_trait]
          ]));
      }
    }
    foreach ($available_traits as $available_trait) {
      if ($available_trait == TRUE && !in_array($available_trait, $required_traits)) {
        $form_state->setErrorByName('traits][#options][' . $available_trait,
          t('The %calculator calculator plugin does not utilize the %trait trait, please disable it.', [
            '%calculator' => $calculator['name'],
            '%trait' => $form['traits']['#options'][$available_trait]
          ]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $period_type = $this->entity;
    $status = $period_type->save();
    $this->submitTraitForm($form, $form_state);
    $message_params = [
      '%label' => $period_type->label(),
      '%content_entity_id' => $period_type->getEntityType()->getBundleOf(),
    ];

    // Provide a message for the user and redirect them back to the collection.
    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label %content_entity_id rental period.', $message_params));
        break;

      default:
        drupal_set_message($this->t('Saved the %label %content_entity_id rental period.', $message_params));
    }

    $form_state->setRedirectUrl($period_type->toUrl('collection'));
  }
}