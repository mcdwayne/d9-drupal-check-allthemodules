<?php

namespace Drupal\dcat\Plugin\DsField\DcatAgent;

use Drupal\ds\Plugin\DsField\Title;

/**
 * Plugin that renders the title of a DCAT agent.
 *
 * @DsField(
 *   id = "dcat_agent_title",
 *   title = @Translation("Title"),
 *   entity_type = "dcat_agent",
 *   provider = "dcat"
 * )
 */
class DcatAgentTitle extends Title {

  /**
   * {@inheritdoc}
   */
  public function entityRenderKey() {
    return 'name';
  }

}
