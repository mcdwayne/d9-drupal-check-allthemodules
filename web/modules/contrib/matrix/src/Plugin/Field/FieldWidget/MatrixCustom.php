<?php 

namespace Drupal\matrix\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;

/**
 * @FieldWidget(
 *  id = "matrix_custom",
 *  label = @Translation("Custom Matrix"),
 *  description = @Translation("A grid of form elements"),
 *  field_types = {"matrix_custom"}
 * )
 */
class MatrixCustom extends WidgetBase {

  /**
   * @FIXME
   * Move all logic relating to the matrix_custom widget into this class.
   * For more information, see:
   *
   * https://www.drupal.org/node/1796000
   * https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Field%21WidgetInterface.php/interface/WidgetInterface/8
   * https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Field%21WidgetBase.php/class/WidgetBase/8
   */

}
