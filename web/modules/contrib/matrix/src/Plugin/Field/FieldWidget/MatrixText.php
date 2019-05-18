<?php 

namespace Drupal\matrix\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;

/**
 * @FieldWidget(
 *  id = "matrix_text",
 *  label = @Translation("Text Matrix"),
 *  description = @Translation("A grid of textfields"),
 *  field_types = {"matrix_text"}
 * )
 */
class MatrixText extends WidgetBase {

  /**
   * @FIXME
   * Move all logic relating to the matrix_text widget into this class.
   * For more information, see:
   *
   * https://www.drupal.org/node/1796000
   * https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Field%21WidgetInterface.php/interface/WidgetInterface/8
   * https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Field%21WidgetBase.php/class/WidgetBase/8
   */

}
