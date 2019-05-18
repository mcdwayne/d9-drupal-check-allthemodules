<?php

namespace Drupal\image_tagger\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Image Tagger formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "image_tagger_image_tagger_formatter",
 *   label = @Translation("Image Tagger formatter"),
 *   field_types = {
 *     "image_tagger_image_tagger_field"
 *   }
 * )
 */
class ImageTaggerFormatterFormatter extends ImageFormatter implements ContainerFactoryPluginInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * ImageTaggerFormatterFormatter constructor.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $entity_type_manager->getStorage('image_style'));
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity.manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $new_elements = [];
    foreach ($elements as $delta => $element) {
      // Generate a unique ID for this field/delta combination.
      $entity = $items->getEntity();
      $id = md5(sprintf('%s_%s_%s_%s', $entity->id(), $entity->getEntityTypeId(), $items->getName(), $delta));
      /** @var \Drupal\image_tagger\Plugin\Field\FieldType\ImageTaggerFieldItem $item */
      $item = $element["#item"];
      $values = $item->getValue();
      $points = (object) [];
      if (!empty($values['data'])) {
        if ($points_json = @json_decode($values['data'])) {
          $points = $points_json;
        }
      }
      /** @var \Drupal\Core\Field\TypedData\FieldItemDataDefinition $definition */
      $definition = $item->getFieldDefinition();
      $field_settings = $definition->getSettings();
      $entity_storage = $this->entityTypeManager->getStorage($field_settings["entity_type"]);
      $entity_view_builder = $this->entityTypeManager->getViewBuilder($field_settings["entity_type"]);
      // Now loop through the points and render the entities.
      foreach ($points_json->points as $point_delta => $point) {
        // Find the entity id.
        if (empty($point->entity)) {
          continue;
        }
        $entity_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($point->entity);
        if (NULL === $id) {
          continue;
        }
        if (!$entity = $entity_storage->load($entity_id)) {
          continue;
        }
        $render_array = $entity_view_builder->view($entity, $field_settings["view_mode"]);
        $point->rendered = $this->renderer->renderRoot($render_array);
      }
      $settings['points'] = [
        $id => [
          'id' => $id,
          'points' => $points,
          'view_mode' => $field_settings['view_mode'],
        ],
      ];
      $new_elements[] = [
        '#prefix' => '<div class="image-tagger-image-wrapper" data-id="' . $id . '">',
        '#suffix' => '</div>',
        'image' => $element,
        '#attached' => [
          'library' => [
            'image_tagger/viewer',
          ],
          'drupalSettings' => [
            'imageTagger' => $settings,
          ],
        ],
      ];
    }
    return $new_elements;
  }

}
