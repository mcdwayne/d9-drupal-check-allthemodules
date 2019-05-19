<?php

namespace Drupal\vud\Plugin\VoteUpDownWidget;

use Drupal\vud\Plugin\VoteUpDownWidgetBase;

/**
 * Provides the "yesno" Vote Up/Down widget
 *
 * @VoteUpDownWidget(
 *   id = "yesno",
 *   admin_label = @Translation("Yes/No"),
 *   description = @Translation("Provides a yes/no widget, together with a percentage of positive votes.")
 *  )
 */
class YesNo extends VoteUpDownWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function alterTemplateVariables(&$variables) {
    parent::alterTemplateVariables($variables);
    if ($variables['#unsigned_points'] == 0) {
      // No votes yet.
      $variables['#up_percent'] = 0;
    }
    else {
      $variables['#up_percent'] = $variables['#up_points'] / $variables['#unsigned_points'] * 100;
    }
    $variables['#percent_text'] = $this->t('<em>@up_percent%</em> found this useful', ['@up_percent' => $variables['#up_percent']]);
  }

}
