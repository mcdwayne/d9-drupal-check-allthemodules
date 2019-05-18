<?php

namespace Drupal\hidden_tab\Plugable\TplContext;

use Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase;

/**
 * Plugin helping adding theme context variables.
 */
interface HiddenTabTplContextInterface extends HiddenTabPluginInterfaceBase {

  const PID = 'hidden_tab_tpl_context';

  /**
   * Provides theme context variables.
   *
   * @param array $entities
   *   Available entities. Probably page, mailer and ...
   * @param array $extra
   *   Extra params. Arbitrary values based on caller.
   *
   * @return array
   *   Array of context variables.
   */
  public function provide(array $entities, array $extra): array;

}
