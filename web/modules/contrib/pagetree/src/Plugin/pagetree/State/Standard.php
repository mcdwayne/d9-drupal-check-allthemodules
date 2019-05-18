<?php
namespace Drupal\pagetree\Plugin\pagetree\State;

use Drupal\pagetree\Plugin\StatePluginBase;

/**
 * @StateHandler(
 *   id = "standard",
 *   name = @Translation("Default render"),
 *   weight = 100
 * )
 */
class Standard extends StatePluginBase
{
    public function annotate(&$entries)
    {
        parent::annotate($entries);
    }
}
