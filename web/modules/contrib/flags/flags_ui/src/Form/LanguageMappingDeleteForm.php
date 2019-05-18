<?php

namespace Drupal\flags_ui\Form;

use Drupal\Core\Url;

/**
 * Class FlagMappingDeleteForm.
 *
 * Provides a confirm form for deleting the entity. This is different from the
 * add and edit forms as it does not inherit from ConfigEntityFormBase. The reason for
 * this is that we do not need to build the same form. Instead, we present the
 * user with a simple yes/no question. For this reason, we derive from
 * EntityConfirmFormBase instead.
 *
 * @package Drupal\flags_languages\Form
 *
 * @ingroup flags_languages
 */
class LanguageMappingDeleteForm extends FlagMappingDeleteForm {

  /**
   * Gets the cancel URL.
   *
   * Provides the URL to go to if the user cancels the action. For entity
   * delete forms, this is typically the route that points at the list
   * controller.
   *
   * @return \Drupal\Core\Url
   *   The URL to go to if the user cancels the deletion.
   */
  public function getCancelUrl() {
    return new Url('entity.language_flag_mapping.list');
  }

}
