<?php

namespace Drupal\image_link\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Annotation\FieldWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'image_link' widget.
 *
 * @FieldWidget(
 *   id = "image_link",
 *   label = @Translation("Image Link"),
 *   field_types = {
 *     "image_link"
 *   }
 * )
 */
class ImageLinkWidget extends ImageWidget implements ContainerFactoryPluginInterface {

  /**
   * Link widget instance.
   *
   * @var \Drupal\link\Plugin\Field\FieldWidget\LinkWidget
   */
  protected $linkWidget;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ElementInfoManagerInterface $element_info, ImageFactory $image_factory = NULL) {
    $image_factory = $image_factory ?: \Drupal::service('image.factory');

    // element info?
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $element_info, $image_factory);

    $this->linkWidget = new LinkWidget($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
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
      $container->get('element_info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $image_element = parent::formElement($items, $delta, $element, $form, $form_state);
    $link_element = $this->linkWidget->formElement($items, $delta, $element, $form, $form_state);

    $element = array_merge($image_element, $link_element);
    // Make things be singular.
    $element['#multiple'] = FALSE;
    return $element;
  }

  /**
   * Overrides \Drupal\file\Plugin\Field\FieldWidget\FileWidget::formMultipleElements().
   *
   * Special handling for draggable multiple widgets and 'add more' button.
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    return WidgetBase::formMultipleElements($items, $form, $form_state);
  }

}
