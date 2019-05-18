<?php

namespace Drupal\courier_ui\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\courier\Service\CourierManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\courier\MessageQueueItemInterface;

/**
 * An example action covering most of the possible options.
 *
 * If type is left empty, action will be selectable for all
 * entity types.
 *
 * @Action(
 *   id = "send_templated_message",
 *   label = @Translation("Send message"),
 *   type = "user",
 *   requirements = {
 *     "_permission" = "send bulk templated messages",
 *   },
 * )
 */
class SendTemplatedMessage extends ViewsBulkOperationsActionBase implements ContainerFactoryPluginInterface, PluginFormInterface {

  protected $entityTypeManager;
  protected $bundleInfo;
  protected $courierManager;

  /**
   * Object constructor.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin Id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfo
   *   Bundle info object.
   * @param \Drupal\courier\Service\CourierManagerInterface $courierManager
   *   Courier manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entityTypeManager,
    EntityTypeBundleInfoInterface $bundleInfo,
    CourierManagerInterface $courierManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->bundleInfo = $bundleInfo;
    $this->courierManager = $courierManager;
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
      $container->get('entity_type.bundle.info'),
      $container->get('courier.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $collection = $this->entityTypeManager->getStorage('courier_template_collection')->load($this->configuration['template_collection']);

    // Filter templates to the selected channels only.
    foreach ($collection->getTemplates() as $template) {
      if (!in_array($template->getEntityTypeId(), $this->configuration['templates']['templates'], TRUE)) {
        $collection->removeTemplate($template->getEntityTypeId());
      }
    }

    // Add referenced entities for token replacement.
    $referenceable_bundles = $collection->referenceable_bundles->getValue();
    if (!empty($referenceable_bundles)) {
      foreach ($referenceable_bundles as $delta => $item) {
        $referenced_entity = $this->entityTypeManager->getStorage($item['entity_type'])->load($this->configuration['templates']['entities'][$delta]);
        $collection->setTokenValue($item['entity_type'], $referenced_entity);
      }
    }

    // Send the message.
    $mqi = $this->courierManager->sendMessage($collection, $entity);
    if ($mqi instanceof MessageQueueItemInterface) {
      return $this->t('message queued for delivery');
    }
    else {
      return $this->t('failed to send message');
    }

  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $templates_id = 'template-templates-wrapper';
    $form['template_collection'] = [
      '#title' => t('Template collection'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'courier_template_collection',
      '#required' => TRUE,
      '#ajax' => [
        'event' => 'autocompleteclose',
        'callback' => [$this, 'formAjax'],
        'wrapper' => $templates_id,
      ],
      '#weight' => 1,
    ];

    $form['templates'] = [
      '#title' => $this->t('Templates'),
      '#type' => 'fieldset',
      '#attributes' => ['id' => $templates_id],
      '#tree' => TRUE,
      '#weight' => 3,
    ];
    $form['templates']['info']['#markup'] = $this->t('Select template collection first.');

    if ($collection_id = $form_state->getValue('template_collection')) {
      $collection = $this->entityTypeManager->getStorage('courier_template_collection')->load($collection_id);

      foreach ($collection->getTemplates() as $template) {
        $template_options[$template->getEntityTypeId()] = $template->getEntityType()->getLabel();
      }
      $form['templates']['templates'] = [
        '#title' => $this->t('Template'),
        '#type' => 'checkboxes',
        '#options' => $template_options,
      ];

      $referenceable_bundles = $collection->referenceable_bundles->getValue();
      if (!empty($referenceable_bundles)) {
        unset($form['templates']['info']);
        foreach ($referenceable_bundles as $delta => $item) {
          $bundle_info = $this->bundleInfo->getBundleInfo($item['entity_type']);
          $form['templates']['entities']['#type'] = 'container';
          if (isset($bundle_info[$item['bundle']])) {
            $definition = $this->entityTypeManager->getDefinition($item['entity_type']);
            $form['templates']['entities'][$delta] = [
              '#type' => 'entity_autocomplete',
              '#title' => $definition->getLabel() . ': ' . $bundle_info[$item['bundle']]['label'],
              '#target_type' => $item['entity_type'],
              '#selection_settings' => [
                'target_bundles' => [$item['bundle']],
              ],
            ];
          }
        }
      }
      else {
        $form['templates']['info']['#markup'] = $this->t('There are no entities to reference.');
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (isset($form['templates']['templates']) && empty($form_state->getValue(['templates', 'templates']))) {
      $form_state->setError($form['templates']['templates'], $this->t('Please select at least one template.'));
    }
  }

  /**
   * Ajax callback.
   */
  public function formAjax(array $form, FormStateInterface $form_state) {
    return $form['templates'];
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $account->hasPermission('send bulk templated messages');
  }

}
