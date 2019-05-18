<?php

namespace Drupal\advertising_products\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

use Drupal\advertising_products\Entity\AdvertisingProduct;
use Drupal\image\Entity\ImageStyle;

/**
 * Plugin implementation of the 'advertising_products_autocomplete_widget' widget.
 *
 * @FieldWidget(
 *   id = "advertising_products_autocomplete_widget",
 *   label = @Translation("Advertising products autocomplete"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class AdvertisingProductsAutocompleteWidget extends EntityReferenceAutocompleteWidget {
  /**
   * {@inheritdoc}
   */
  public static function afterBuild(array $element, FormStateInterface $form_state) {
    foreach ($element as $idx => $sub_element) {
      if (is_numeric($idx) && isset($sub_element['target_id']) && $sub_element['target_id']['#target_type'] == 'advertising_product') {
        // overwrite #autocomplete_route_name
        $element[$idx]['target_id']['#autocomplete_route_name'] = 'advertising_products.autocomplete';
        // we also need to change the data-autocomplete-path attribute
        $parameters = isset($sub_element['target_id']['#autocomplete_route_parameters']) ? $sub_element['target_id']['#autocomplete_route_parameters'] : [];
        $url = Url::fromRoute($element[$idx]['target_id']['#autocomplete_route_name'], $parameters)->toString(TRUE);
        $element[$idx]['target_id']['#attributes']['data-autocomplete-path'] = $url->getGeneratedUrl();
      }
    }
    return $element;
  }

  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['target_id']['#selection_handler'] = 'advertising_products:product';

    // Highlight sold-out products
    $element['target_id']['#attached']['library'][] = 'advertising_products/form';
    $element['target_id']['#attributes']['class'][] = 'advertising-products-autocomplete';

    $isSoldOut = $items[$delta]->entity instanceof AdvertisingProduct && $items[$delta]->entity->product_sold_out->value;
    if ($isSoldOut) {
      $element['target_id']['#attributes']['class'][] = 'status-red';
    }

    $changedState = $form_state->getValue($items->getName());
    if ($changedState && $changedState[$delta]['target_id'] !== $items[$delta]->target_id) {
      $product = AdvertisingProduct::load($changedState[$delta]['target_id']);
    }
    else {
      $product = $product = $items[$delta]->entity;
    }

    $this->getImages($product, $element);

    return $element;
  }

  protected function getImages($product, &$element) {
    $primary = null;
    $alternatives = null;
    $isNew = true;

    if ($product) {
      $alternatives = $product->extra_images;

      $file = $product->product_image->get(0)->entity;
      $primary = ImageStyle::load('thumbnail')->buildUrl($file->getFileUri());

      $isNew = false;
    }

    $element['image_selection'] = [
      '#theme' => 'advertising_products_image_selection',
      '#is_new' => $isNew,
      '#primary_image' => $primary,
      '#images' => $alternatives,
      '#attached' => ['library' => ['advertising_products/image_selection']],
      '#weight' => "99"
    ];
    $element['image_selection_input'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'class' => 'advertising-products__images--input'
      ]
    ];
    $element['image_selection_opened'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'class' => 'advertising-products__open-alternatives'
      ]
    ];
  }

  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);

    // Only handle saving side effects, if form is submitted to be saved.
    $triggeringElement = $form_state->getTriggeringElement();
    if ($triggeringElement['#submit'][0][1] !== 'addMoreSubmit') {

      foreach ($values as $value) {
        $product = \Drupal::entityTypeManager()->getStorage('advertising_product')->load($value['target_id']);
        if ($providerName = $product->product_provider->value) {
          $provider = \Drupal::service('plugin.manager.advertising_products.provider')->createInstance($providerName);
          $provider->submitFieldWidget($value);
        }
      }

    }

    return $values;
  }

}
