<?php
namespace Drupal\smart_content_block\Plugin\smart_content\Variation;
/**
 * Created by PhpStorm.
 * User: mike
 * Date: 2/28/19
 * Time: 6:08 PM
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\smart_content\Form\SmartVariationSetForm;
use Drupal\smart_content\Variation\VariationBase;

/**
 * Provides a default Smart Condition.
 *
 * @SmartVariation(
 *   id = "variation_block",
 *   label = @Translation("View Mode Variation"),
 * )
 */
class VariationBlock extends VariationBase {


  function getReactionPluginId() {
    return 'block';
  }

}
