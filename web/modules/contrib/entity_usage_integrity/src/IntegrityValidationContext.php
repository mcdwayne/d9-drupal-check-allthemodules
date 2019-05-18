<?php

namespace Drupal\entity_usage_integrity;

/**
 * Define contexts of entity usage integrity validation.
 */
final class IntegrityValidationContext {

  /**
   * Occurs when entity edit form is viewed.
   *
   * @var string
   */
  const EDIT_FORM_VIEW = 'entity_edit_form_view';

  /**
   * Occurs when entity delete form is viewed.
   *
   * @var string
   */
  const DELETE_FORM_VIEW = 'entity_delete_form_view';

  /**
   * Occurs when entity is being saved.
   *
   * @var string
   */
  const ENTITY_SAVE = 'entity_save';

}
