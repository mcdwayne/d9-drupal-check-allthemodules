<?php

namespace Drupal\wisski_core\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow; 
use Drupal\views\Render\ViewsRenderPipelineMarkup;

/**
 * Default implementation of the base field plugin.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("wisski_preview_image")
 */
class PreviewImage extends FieldPluginBase {
  
  /**
   * {@inheritdoc}
   */ 
  public function render(ResultRow $values) {
    
    $value = $this->getValue($values);
#    dpm($value);
    if (is_array($value)) {
      $return = [];
      foreach ($value as $v) {
        $return[] = ViewsRenderPipelineMarkup::create($v); #$this->sanitizeValue($v);
      }
      return join(', ', $return);
    }
    else {
      return ViewsRenderPipelineMarkup::create($value); #$this->sanitizeValue($value);
    }
  }

}   

