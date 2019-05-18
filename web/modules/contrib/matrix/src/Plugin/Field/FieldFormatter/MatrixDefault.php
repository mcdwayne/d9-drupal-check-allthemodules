<?php

namespace Drupal\matrix\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;

/**
 * @FieldFormatter(
 *  id = "matrix_default",
 *  label = @Translation("Default"),
 *  field_types = {"matrix_text&quot;, &quot;matrix_custom"}
 * )
 */
class MatrixDefault extends FormatterBase {

  /**
   * @FIXME
   * Move all logic relating to the matrix_default formatter into this
   * class. For more information, see:
   *
   * https://www.drupal.org/node/1805846
   * https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Field%21FormatterInterface.php/interface/FormatterInterface/8
   * https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Field%21FormatterBase.php/class/FormatterBase/8
   */

}
