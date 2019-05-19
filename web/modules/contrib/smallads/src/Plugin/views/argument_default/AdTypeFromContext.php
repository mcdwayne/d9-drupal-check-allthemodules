<?php

namespace Drupal\smallads\Plugin\views\argument_default;

use Drupal\smallads\Entity\SmalladType;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;

/**
 * The fixed argument default handler.
 *
 * Works out from the url what ad type it is. Especially useful for blocks.
 *
 * @todo test caching of a view or block
 *
 * @ingroup views_argument_default_plugins
 *
 * @ViewsArgumentDefault(
 *   id = "ad_type_from_context",
 *   title = @Translation("Smallad type from route context")
 * )
 */
class AdTypeFromContext extends ArgumentDefaultPluginBase {

  /**
   * {@inheritdoc}
   */
  public function access() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['fallback'] = ['default' => 'showall'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    if ($type = smallad_type_from_route_match()) {
      return $type;
    }
    $this->view->build_info['fail'] = TRUE;
    return;
    // Can't get the following to work.
    $fallback = $this->options['fallback'];
    if ($fallback == 'hide') {
      // This should hide the view.
      $this->view->build_info['fail'] = TRUE;
    }
    elseif ($fallback == 'showall') {
      $types = SmalladType::loadMultiple();
      return array_keys($types);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isCacheable() {
    return FALSE;
  }

}
