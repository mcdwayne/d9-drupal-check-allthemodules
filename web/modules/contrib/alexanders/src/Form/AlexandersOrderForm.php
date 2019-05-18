<?php

namespace Drupal\alexanders\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityFormBuilder;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the commerce_order entity edit forms.
 */
class AlexandersOrderForm extends ContentEntityForm {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;
  protected $formBuilder;

  /**
   * Constructs a new OrderForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, DateFormatterInterface $date_formatter, EntityFormBuilder $form_builder) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);

    $this->dateFormatter = $date_formatter;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('date.formatter'),
      $container->get('entity.form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\alexanders\Entity\AlexandersOrder $order */
    $order = $this->entity;
    $form = parent::form($form, $form_state);

    $form['#tree'] = TRUE;

    $form['order_data'] = ['#type' => 'fieldset', '#title' => $this->t('Order Info')];
    $due = $order->getDue() ? $this->dateFormatter->format($order->getDue()) : t('Waiting for Alexanders');
    $form['order_data']['due'] = [
      '#markup' => t('Due Date: @date', ['@date' => $due]),
    ];

    $form['order_data']['rush_order'] = [
      '#title' => t('Rush Order'),
      '#default_value' => $order->getRush(),
      '#type' => 'checkbox',
    ];
    $form['order_items'] = ['#type' => 'fieldset', '#title' => $this->t('Order Items')];
    foreach ($order->getItems() as $item) {
      $form['order_items'][$item->id()] = ['#type' => 'fieldset', '#title' => $item->label()];
      $form['order_items'][$item->id()]['data'] = $this->formBuilder->getForm($item);
    }

    foreach ($order->getShipment() as $item) {
      $form['order_shipment'][$item->id()] = ['#type' => 'fieldset', '#title' => $item->label()];
      $form['order_shipment'][$item->id()]['data'] = $this->formBuilder->getForm($item);
    }
    return $form;
  }

  /**
   * Builds a read-only form element for a field.
   *
   * @param string $label
   *   The element label.
   * @param string $value
   *   The element value.
   *
   * @return array
   *   The form element.
   */
  protected function fieldAsReadOnly($label, $value) {
    return [
      '#type' => 'item',
      '#wrapper_attributes' => [
        'class' => [Html::cleanCssIdentifier(strtolower($label)), 'container-inline'],
      ],
      '#markup' => '<h4 class="label inline">' . $label . '</h4> ' . $value,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $this->messenger()->addMessage($this->t('The order %label has been successfully saved.', ['%label' => $this->entity->label()]));
    $form_state->setRedirect('entity.commerce_order.canonical', ['commerce_order' => $this->entity->id()]);
  }

}
