<?php

namespace Drupal\social_share;

use Drupal\Component\Plugin\ContextAwarePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface for social share links.
 *
 * @todo: Allow preparing of multi-rendering.
 */
interface SocialShareLinkInterface extends PluginInspectionInterface, ContextAwarePluginInterface {

  /**
   * Gets the render array for the link.
   *
   * Before calling this, all required context must be set on the plugin.
   *
   * @param string $template_suffix
   *   (optional) A suffix to append to the template name. This should be used
   *   to support optional template suggestions depending on the callers
   *   context, e.g. the block or entity name. The string must include the
   *   leading two underscores, e.g. values would be "__node" or
   *   "__node__field_social".
   * @param mixed[] $render_context
   *   (optional) An array of additional render context to add to the links
   *   render array. This allows adding custom template suggestions based
   *   upon the provided rendered context via hook_theme_suggestions_HOOK().
   *
   * @return mixed[]
   *   The render array.
   */
  public function build($template_suffix = '', $render_context = []);

  /**
   * Gets the template info for the link's template(s).
   *
   * Note that there as no plugin configuration available when this method is
   * called.
   *
   * @return array[]
   *   An array as it would be returned by a hook_theme() implementation.
   */
  public function getTemplateInfo();

}
