<?php

/**
 * @file
 * Contains \Drupal\product_gallery\Plugin\Field\FieldFormatter\ImageTitleCaption.
 */

namespace Drupal\product_gallery\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Plugin implementation of the 'image_title_caption' formatter.
 *
 * @FieldFormatter(
 *   id = "image_title_caption",
 *   label = @Translation("Product Gallery"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ProductGallery extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items,  $langcode) {
    // Skip if there are no items to display.
    if ($items->isEmpty()) {
      return [];
    }	 

    //$standalone_html_id = Html::getUniqueId($items->getName() . '-standalone');
    //$carousel_id = Html::getUniqueId($items->getName() . '-nav');
    //$lazy_load = $this->getSetting('lazy_load');
    //$use_lazy_load = $lazy_load != 'none';

    // Retrieve file IDs.
    $fids = [];
    foreach ($items->getIterator() AS $item) {
      $fids[] = $item->get('target_id')->getValue();
    }

    // Load files so we can get their URIs.
    $files = \Drupal::entityManager()->getStorage('file')->loadMultiple($fids);

    // Make a list of all URIs.
    $uris = array_map(function($file) {
      return $file->getFileUri();
    }, $files);

    // Set up the render element.
    $element = [
      '#attached' => [
        'library' => [
          'product_gallery/productGalleryLibrary'
        ]
      ]
    ];



    // Create the main image carousel.
    $element['carousel'] = [
      '#theme' => 'product_image_list',
      '#items' => [],
      '#weight' => 1,
      '#attributes' => [
      //  'id' => $carousel_id,
        'class' => [
          'sp-wrap',
          //$this->getSetting('vertical') ? 'vertical' : 'horizontal'
        ],
       /* 'data-slick-settings' => json_encode([
          'slidesToShow' => (int) $this->getSetting('visible_slides'),
          'slidesToScroll' => 1,
          'asNavFor' => '#' . $standalone_html_id,
          'dots' => (bool) $this->getSetting('dots'),
          'arrows' => (bool) $this->getSetting('arrows'),
          'fade' => (bool) $this->getSetting('fade'),
          'vertical' => (bool) $this->getSetting('vertical'),
          'verticalSwiping' => (bool) $this->getSetting('vertical'),
          'focusOnSelect' => TRUE,
          'lazyLoad' => $lazy_load,
          'mobileFirst' => TRUE,
          //'rtl' => \Drupal::languageManager()->getCurrentLanguage()->getDirection() == 'rtl',
          'infinite' => FALSE
        ])*/
      ]
    ];

    foreach ($uris AS $uri) {
      $element['carousel']['#items'][] = $this->getImageItem($uri, $this->getSetting('image_style'));
    }
   // print'<pre>';print_r($element);exit;
	  
/*    $elements = parent::viewElements($items);
    foreach ($elements as &$element) {
      $element['#theme'] = 'image_title_caption_formatter';
    }
*/
    return [$element];
  }



  /**
   * Helper method to generate image for provided style
   * that takes the lazy load setting into account.
   *
   * @param string $uri
   *   The original image uri.
   * @param string $image_style
   *   The image style name.
   * @param bool $lazy_load
   *   If TRUE the lazy slick load image will be returned.
   *
   * @return array
   *   Renderable array for a single image.
   */
  function getImageItem($uri, $image_style, $lazy_load = FALSE) {
      
      $style = \Drupal::entityManager()
        ->getStorage('image_style')
        ->load($image_style);
      return [
        '#type' => 'inline_template',
        '#template' => '<a href="{{ path }}"><img src="{{ path }}" /></a>',
        '#context' => [
          'path' => $style ? $style->buildUrl($uri) : file_create_url($uri)
        ]
      ];	  
    /*if ($lazy_load) {
      $style = \Drupal::entityManager()
        ->getStorage('image_style')
        ->load($image_style);

      return [
        '#type' => 'inline_template',
        '#template' => '<img data-lazy="{{ path }}" />',
        '#context' => [
          'path' => $style ? $style->buildUrl($uri) : file_create_url($uri)
        ]
      ];
    } else {
      return [
        '#theme' => 'image_style',
        '#uri' => $uri,
        '#style_name' => $image_style
      ];
    }*/
  }

}
