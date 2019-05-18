<?php

namespace Drupal\bibcite_entity\Plugin\Field\FieldWidget;

use Drupal\bibcite_entity\ContributorPropertiesServiceInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'bibcite_contributor_widget' widget.
 *
 * @FieldWidget(
 *   id = "bibcite_contributor_widget",
 *   label = @Translation("Contributor widget"),
 *   field_types = {
 *     "bibcite_contributor"
 *   }
 * )
 */
class ContributorWidget extends EntityReferenceAutocompleteWidget implements ContainerFactoryPluginInterface {

  /**
   * The contributor category manager service.
   *
   * @var \Drupal\bibcite_entity\ContributorPropertiesServiceInterface
   */
  protected $contributorPropertiesService;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ContributorPropertiesServiceInterface $contributorPropertiesService) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->contributorPropertiesService = $contributorPropertiesService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('bibcite_entity.contributor_properties_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element += parent::formElement($items, $delta, $element, $form, $form_state);

    $links = [
      ':category' => Url::fromRoute('entity.bibcite_contributor_category.collection')->toString(),
      ':role' => Url::fromRoute('entity.bibcite_contributor_role.collection')->toString(),
    ];
    $element['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#default_value' => isset($items[$delta]->category) ? $items[$delta]->category : $this->contributorPropertiesService->getDefaultCategory(),
      '#description' => $this->t('Default category value can be set on <a href=":category">settings page</a>.', $links),
      '#maxlength' => $this->getFieldSetting('max_length'),
      '#options' => $this->contributorPropertiesService->getCategories(),
      '#empty_option' => $this->t('- Default -'),
      '#weight' => $delta,
      '#prefix' => '<div class="bibcite-contributor__selects">',
    ];

    $element['role'] = [
      '#type' => 'select',
      '#title' => $this->t('Role'),
      '#default_value' => isset($items[$delta]->role) ? $items[$delta]->role : $this->contributorPropertiesService->getDefaultRole(),
      '#description' => $this->t('Default role value can be set on <a href=":role">settings page</a>.', $links),
      '#maxlength' => $this->getFieldSetting('max_length'),
      '#options' => $this->contributorPropertiesService->getRoles(),
      '#empty_option' => $this->t('- Default -'),
      '#weight' => $delta,
      '#suffix' => '</div>',
    ];

    $entity = $items->getEntity();
    $element['target_id']['#autocreate'] = [
      'bundle' => 'bibcite_contributor',
      'uid' => ($entity instanceof EntityOwnerInterface) ? $entity->getOwnerId() : \Drupal::currentUser()->id(),
    ];

    $element['#attached']['library'][] = 'bibcite_entity/widget';

    return $element;
  }

}
