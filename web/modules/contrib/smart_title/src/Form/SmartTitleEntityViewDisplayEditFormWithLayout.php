<?php

namespace Drupal\smart_title\Form;

use Drupal\field_layout\Form\FieldLayoutEntityViewDisplayEditForm;

/**
 * Edit form for the EntityViewDisplay entity type.
 */
class SmartTitleEntityViewDisplayEditFormWithLayout extends FieldLayoutEntityViewDisplayEditForm {

  use SmartTitleEntityDisplayFormTrait;

}
