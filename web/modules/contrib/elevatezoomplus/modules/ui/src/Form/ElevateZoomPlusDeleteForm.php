<?php

namespace Drupal\elevatezoomplus_ui\Form;

use Drupal\Core\Url;
use Drupal\slick_ui\Form\SlickDeleteFormBase;

/**
 * Builds the form to delete a ElevateZoomPlus optionset.
 */
class ElevateZoomPlusDeleteForm extends SlickDeleteFormBase {

  /**
   * Defines the nice anme.
   *
   * @var string
   */
  protected static $niceName = 'ElevateZoomPlus';

  /**
   * Defines machine name.
   *
   * @var string
   */
  protected static $machineName = 'elevatezoomplus';

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.elevatezoomplus.collection');
  }

}
