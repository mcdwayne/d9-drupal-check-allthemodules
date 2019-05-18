<?php

namespace Drupal\contact_default_fields_override\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\field_ui\Controller\FieldConfigListController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ContactMessageFieldConfigListController.
 *
 * This overrides the FieldConfigListController controller for the
 * contact_message entities so we can add the fields to override to the fields
 * listing.
 *
 * @package Drupal\contact_default_fields_override\Controller
 */
class ContactMessageFieldConfigListController extends FieldConfigListController implements ContainerInjectionInterface {

  /**
   * The dependency injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * ContactMessageFieldConfigListController constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The dependency injection container.
   */
  public function __construct(ContainerInterface $container) {
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  public function listing($entity_type_id = NULL, $bundle = NULL, RouteMatchInterface $route_match = NULL) {
    $listing = parent::listing($entity_type_id, $bundle, $route_match);

    /* @var \Drupal\Core\Entity\EntityFieldManager $entityFieldManager */
    $entityFieldManager = $this->container->get('entity_field.manager');

    $baseFieldDefinitions = $entityFieldManager->getBaseFieldDefinitions($entity_type_id);
    $fieldDefinitions = $entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);

    foreach (contact_default_fields_override_get_fields_to_override() as $field_to_override) {

      $url = Url::fromRoute('contact_default_fields_override.overrideform', [
        'contact_form' => $bundle,
        'field_name' => $field_to_override,
      ]);

      $listing['table']['#rows']['contact_default_fields_override_' . $field_to_override] = [
        'id' => 'contact_default_fields_override_' . $field_to_override,
        'data' => [
          'label' => $fieldDefinitions[$field_to_override]->getLabel(),
          'field_name' => $this->t('<i>@label</i> field from <i>Contact</i> module', ['@label' => $baseFieldDefinitions[$field_to_override]->getLabel()]),
          'field_type' => '',
          'operations' => [
            'data' => [
              '#type' => 'operations',
              '#links' => [
                'edit' => [
                  'title' => $this->t('Edit'),
                  'url' => $url,
                ],
              ],
            ],
          ],
        ],
      ];
    }

    return $listing;
  }

  /**
   * Creates a new instance.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The dependency injection container.
   *
   * @return \Drupal\contact_default_fields_override\Controller\ContactMessageFieldConfigListController|\Drupal\field_ui\Controller\FieldConfigListController
   *   The new instance.
   */
  public static function create(ContainerInterface $container) {
    return new static($container);
  }

}
