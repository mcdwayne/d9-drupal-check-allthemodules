<?php

namespace Drupal\dcat\Plugin\DsField\DcatVcard;

use Drupal\ds\Plugin\DsField\Title;

/**
 * Plugin that renders the title of a DCAT Vcard.
 *
 * @DsField(
 *   id = "dcat_vcard_title",
 *   title = @Translation("Title"),
 *   entity_type = "dcat_vcard",
 *   provider = "dcat"
 * )
 */
class DcatVcardTitle extends Title {

  /**
   * {@inheritdoc}
   */
  public function entityRenderKey() {
    return 'name';
  }

}
