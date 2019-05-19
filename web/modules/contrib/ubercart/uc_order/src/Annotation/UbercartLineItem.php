<?php

namespace Drupal\uc_order\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a line item plugin annotation object.
 *
 * @Annotation
 */
class UbercartLineItem extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The title of the line item.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The plugin weight.
   *
   * @var int
   */
  public $weight;

  /**
   * Whether or not the line item will be stored in the database.
   *
   * Should be TRUE for any line item that is modifiable from the
   * order edit screen.
   *
   * @var bool
   */
  public $stored = FALSE;

  /**
   * Include this in the admin "Add a Line Item" selection.
   *
   * Whether or not a line item should be included in the "Add a Line Item"
   * select box on the order edit screen.
   *
   * @var bool
   */
  public $add_list = FALSE;

  /**
   * For inclusion into the order total.
   *
   * Whether or not the value of this line item should be added to the order
   * total. (Ex: would be TRUE for a shipping charge line item but FALSE for
   * the subtotal line item since the product prices are already taken into
   * account.)
   *
   * @var bool
   */
  public $calculated = FALSE;

  /**
   * For display only - this item is derived from other information.
   *
   * Whether or not this line item is simply a display of information but not
   * calculated anywhere. (Ex: the total line item uses display to simply show
   * the total of the order at the bottom of the list of line items.)
   *
   * @var bool
   */
  public $display_only = FALSE;

}
