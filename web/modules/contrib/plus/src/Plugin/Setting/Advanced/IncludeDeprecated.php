<?php
/**
 * @file
 * Contains \Drupal\plus\Plugin\Setting\Advanced\IncludeDeprecated.
 */

namespace Drupal\plus\Plugin\Setting\Advanced;

use Drupal\plus\Plugin\Setting\SettingBase;
use Drupal\Core\Annotation\Translation;

/**
 * The "include_deprecated" theme setting.
 *
 * @ingroup plugins_setting
 *
 * @Setting(
 *   id = "include_deprecated",
 *   type = "checkbox",
 *   weight = -3,
 *   title = @Translation("Include deprecated functions"),
 *   defaultValue = 0,
 *   description = @Translation("Enabling this setting will include any <code>deprecated.php</code> file found in your theme or base themes."),
 *   groups = {
 *     "advanced" = @Translation("Advanced"),
 *   },
 * )
 */
class IncludeDeprecated extends SettingBase {}
