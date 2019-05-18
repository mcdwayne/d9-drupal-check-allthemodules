<?php

namespace Drupal\micro_site\Plugin\EntityReferenceSelection;

use Drupal\micro_site\Plugin\EntityReferenceSelection\SiteSelection;

/**
 * Provides entity reference selections for the domain entity type.
 *
 * @EntityReferenceSelection(
 *   id = "site:site",
 *   label = @Translation("Site administrator selection"),
 *   base_plugin_label = @Translation("Site administrator"),
 *   entity_types = {"site"},
 *   group = "site",
 *   weight = 5
 * )
 */
class SiteAdminSelection extends SiteSelection {

  /**
   * Sets the context for the alter hook.
   *
   * The only difference between this selector and its parent are the
   * permissions used to restrict access. Since the field information is not
   * available through the DefaultSelector class, we have to coerce that
   * information to pass it to our hook.
   *
   * We could do this by reading the id from the annotation, but setting an
   * explicit variable seems more obvious for developers.
   *
   * @var string
   */
  protected $field_type = 'admin';

}
