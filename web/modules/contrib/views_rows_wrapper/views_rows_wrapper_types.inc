<?php

namespace Drupal\views_rows_wrapper;

/**
 * Views Rows Wrapper helper class.
 */
class ViewsRowsWrapperTypes {

  /**
   * Returns wrapper element types()
   */
  public static function elementTypes() {
    return ["DIV", "SPAN"];
  }

  /**
   * Returns wrapper attribute types()
   */
  public static function attributeTypes() {
    return ["CLASS", "ID"];
  }

}
