<?php

namespace Drupal\entity_pilot_map_config\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_pilot_map_config\BundleMappingInterface;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides entity form for managing bundle mappings.
 */
class BundleMappingForm extends EntityForm {

  /**
   * Entity bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityBundleInfo;

  /**
   * Constructs a new BundleMappingForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_bundle_info
   *   Entity bundle info service.
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_bundle_info) {
    $this->entityBundleInfo = $entity_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.bundle.info'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    if ($this->getRequest()->query->get('message', FALSE)) {
      $form['message'] = [
        '#theme' => 'status_messages',
        '#message_list' => [
          'warning' => [
            $this->t('Please configure how to handle missing bundles.'),
          ],
        ],
      ];
    }
    /** @var \Drupal\entity_pilot_map_config\BundleMappingInterface $ep_bundle_mapping */
    $ep_bundle_mapping = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $ep_bundle_mapping->label(),
      '#description' => $this->t("Label for the Bundle mapping."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $ep_bundle_mapping->id(),
      '#machine_name' => [
        'exists' => '\Drupal\entity_pilot_map_config\Entity\BundleMapping::load',
      ],
      '#disabled' => !$ep_bundle_mapping->isNew(),
    ];

    $form['mappings'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Entity type'),
        $this->t('Source bundle'),
        $this->t('Destination bundle'),
      ],
    ];

    $entity_type_options = [];

    foreach ($ep_bundle_mapping->getMappings() as $delta => $mapping) {
      $entity_type = $mapping['entity_type'];
      if (!isset($entity_type_options[$entity_type])) {
        $entity_type_options[$entity_type] = array_map(function (array $item) {
          return $item['label'];
        }, $this->entityBundleInfo->getBundleInfo($entity_type)) + [
          BundleMappingInterface::IGNORE_BUNDLE => $this->t('Ignore'),
        ];
      }
      $form['mappings'][$delta] = [
        'entity_type_display' => [
          '#markup' => $mapping['entity_type'],
        ],
        'source_bundle_name_display' => [
          '#markup' => $mapping['source_bundle_name'],
        ],
        'destination_bundle_name' => [
          '#type' => 'select',
          '#title_display' => 'invisible',
          '#options' => $entity_type_options[$entity_type],
          '#default_value' => $mapping['destination_bundle_name'],
        ],
      ];
      $form['values'][$delta] = [
        'entity_type' => [
          '#type' => 'value',
          '#parents' => ['mappings', $delta, 'entity_type'],
          '#value' => $mapping['entity_type'],
        ],
        'source_bundle_name' => [
          '#type' => 'value',
          '#parents' => ['mappings', $delta, 'source_bundle_name'],
          '#value' => $mapping['source_bundle_name'],
        ],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $ep_bundle_mapping = $this->entity;
    $status = $ep_bundle_mapping->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Bundle mapping.', [
          '%label' => $ep_bundle_mapping->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Bundle mapping.', [
          '%label' => $ep_bundle_mapping->label(),
        ]));
    }
    $request = $this->getRequest();
    if (($destinations = $request->query->get('destinations')) && $next_destination = FieldUI::getNextDestination($destinations)) {
      $request->query->remove('destinations');
      $form_state->setRedirectUrl($next_destination);
    }
    else {
      $form_state->setRedirectUrl($ep_bundle_mapping->toUrl('collection'));
    }
  }

}
