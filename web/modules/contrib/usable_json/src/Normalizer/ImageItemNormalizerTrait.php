<?php

namespace Drupal\usable_json\Normalizer;

use Drupal\breakpoint\BreakpointInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;

/**
 * A trait providing ItemItem normalizing decorating methods.
 */
trait ImageItemNormalizerTrait {

  /**
   * Adds image style information to normalized ImageItem field data.
   *
   * @param \Drupal\image\Plugin\Field\FieldType\ImageItem $item
   *   The image field item.
   * @param array $normalization
   *   The image field normalization to add image style information to.
   * @param array $context
   *   Context options for the normalizer.
   */
  protected function decorateWithImageStyles(ImageItem $item, array &$normalization, array $context) {
    $config = \Drupal::config('usable_json.api');

    /** @var \Drupal\file\FileInterface $image */
    if ($image = File::load($item->target_id)) {
      $uri = $image->getFileUri();
      /** @var \Drupal\image\ImageStyleInterface[] $styles */
      $styles = ImageStyle::loadMultiple();
      $normalization['image_styles'] = [];
      $normalization['responsive_image_styles'] = [];
      $normalization['url'] = $image->url();
      $normalization['type'] = 'image';

      unset($normalization['derivatives']);

      if ($config->get('enable_image_styles')) {
        foreach ($styles as $id => $style) {
          if ($style->supportsUri($uri)) {
            $dimensions = ['width' => $item->width, 'height' => $item->height];
            $style->transformDimensions($dimensions, $uri);
            $normalization['image_styles'][$id] = [
              'url' => $style->buildUrl($image->getFileUri()),
              'height' => empty($dimensions['height']) ? NULL : $dimensions['height'],
              'width' => empty($dimensions['width']) ? NULL : $dimensions['width'],
            ];
            if (!empty($context['cacheability'])) {
              $context['cacheability']->addCacheableDependency($style);
            }
          }
        }
      }

      if ($config->get('enable_responsive_image_styles')) {
        $responsive_styles = ResponsiveImageStyle::loadMultiple();
        foreach ($responsive_styles as $id => $responsive_style) {
          $sources = [];
          $breakpoints = array_reverse(\Drupal::service('breakpoint.manager')
            ->getBreakpointsByGroup($responsive_style->getBreakpointGroup()));
          if (!empty($context['cacheability'])) {
            $context['cacheability']->addCacheableDependency($responsive_style);
          }
          foreach ($responsive_style->getKeyedImageStyleMappings() as $breakpoint_id => $multipliers) {
            if (isset($breakpoints[$breakpoint_id])) {
              $sources[] = $this->responsiveImageBuildSourceAttributes($image, $breakpoints[$breakpoint_id], $multipliers);
            }
          }
          $normalization['responsive_image_styles'][$id]['fallback'] = $this->responsiveImageStyleUrl($responsive_style->getFallbackImageStyle(), $uri);
          $normalization['responsive_image_styles'][$id]['sources'] = $sources;
        }
      }
    }
  }

  /**
   * Responsive image build responsive source attributes.
   *
   * @param \Drupal\file\Entity\File $image
   *   Image file.
   * @param \Drupal\breakpoint\BreakpointInterface $breakpoint
   *   The breakpoints.
   * @param array $multipliers
   *   Multipliers for the image.
   *
   * @return array
   *   Return array.
   */
  private function responsiveImageBuildSourceAttributes(File $image, BreakpointInterface $breakpoint, array $multipliers) {
    $file_uri = $image->getFileUri();
    $image = \Drupal::service('image.factory')->get($file_uri);
    $width = $image->getWidth();
    $height = $image->getHeight();

    $extension = pathinfo($file_uri, PATHINFO_EXTENSION);
    $sizes = [];
    $srcset = [];
    $derivative_mime_types = [];
    foreach ($multipliers as $multiplier => $image_style_mapping) {
      switch ($image_style_mapping['image_mapping_type']) {
        // Create a <source> tag with the 'sizes' attribute.
        case 'sizes':
          // Loop through the image styles for this breakpoint and multiplier.
          foreach ($image_style_mapping['image_mapping']['sizes_image_styles'] as $image_style_name) {
            // Get the dimensions.
            $dimensions = responsive_image_get_image_dimensions($image_style_name, [
              'width' => $width,
              'height' => $height,
            ], $file_uri);
            // Get MIME type.
            $derivative_mime_type = responsive_image_get_mime_type($image_style_name, $extension);
            $derivative_mime_types[] = $derivative_mime_type;

            // Add the image source with its width descriptor. When a width
            // descriptor is used in a srcset, we can't add a multiplier to
            // it. Because of this, the image styles for all multipliers of
            // this breakpoint should be merged into one srcset and the sizes
            // attribute should be merged as well.
            if (is_null($dimensions['width'])) {
              throw new \LogicException("Could not determine image width for '{$file_uri}' using image style with ID: $image_style_name. This image style can not be used for a responsive image style mapping using the 'sizes' attribute.");
            }
            // Use the image width as key so we can sort the array later on.
            // Images within a srcset should be sorted from small to large,
            // since
            // the first matching source will be used.
            $srcset[intval($dimensions['width'])] = $this->responsiveImageStyleUrl($image_style_name, $file_uri) . ' ' . $dimensions['width'] . 'w';
            $sizes = array_merge(explode(',', $image_style_mapping['image_mapping']['sizes']), $sizes);
          }
          break;

        case 'image_style':
          // Get MIME type.
          $derivative_mime_type = responsive_image_get_mime_type($image_style_mapping['image_mapping'], $extension);
          $derivative_mime_types[] = $derivative_mime_type;
          // Add the image source with its multiplier. Use the multiplier as key
          // so we can sort the array later on. Multipliers within a srcset should
          // be sorted from small to large, since the first matching source will
          // be used. We multiply it by 100 so multipliers with up to two decimals
          // can be used.
          $srcset[intval(Unicode::substr($multiplier, 0, -1) * 100)] = $this->responsiveImageStyleUrl($image_style_mapping['image_mapping'], $file_uri) . ' ' . $multiplier;
          break;
      }
    }

    ksort($srcset);
    $return = [
      'srcset' => implode(', ', array_unique($srcset)),
    ];

    $media_query = trim($breakpoint->getMediaQuery());
    if (!empty($media_query)) {
      $return['media'] = $media_query;
    }
    if (count(array_unique($derivative_mime_types)) == 1) {
      $return['type'] = $derivative_mime_types[0];
    }
    if (!empty($sizes)) {
      $return['sizes'] = implode(',', array_unique($sizes));
    }
    return $return;
  }

  /**
   * Wrapper around image_style_url() so we can return an empty image.
   *
   * @param string $style_name
   *   Image style name.
   * @param string $path
   *   Path.
   *
   * @return \Drupal\Core\GeneratedUrl|string
   *   Return url.
   */
  private function responsiveImageStyleUrl($style_name, $path) {
    if ($style_name == RESPONSIVE_IMAGE_EMPTY_IMAGE) {
      // The smallest data URI for a 1px square transparent GIF image.
      // http://probablyprogramming.com/2009/03/15/the-tiniest-gif-ever
      return 'data:image/gif;base64,R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
    }
    $entity = ImageStyle::load($style_name);
    if ($entity instanceof ImageStyle) {
      return $entity->buildUrl($path);
    }
    return file_create_url($path);
  }

}
