<?php

namespace Drupal\drd\Plugin\Block;

use Drupal\Core\Url;

/**
 * Provides a 'WidgetIntro' block.
 *
 * @Block(
 *  id = "drd_intro",
 *  admin_label = @Translation("DRD Intro"),
 *  weight = -99,
 *  tags = {"drd_widget"},
 * )
 */
class WidgetIntro extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  protected function title() {
    return $this->t('Intro');
  }

  /**
   * {@inheritdoc}
   */
  protected function content() {
    $build = [];

    $build[] = [
      '#markup' => $this->t('<p>Using Drupal Remote Dashboard is simple and the online 
  <a href="https://www.drupal.org/docs/8/modules/drupal-remote-dashboard">documentation</a>
  should help you with the details.</p>
<p>This main dashboard provides you with an overview and points you the right
  direction for what you need to focus on.</p>'),
    ];

    if (\Drupal::currentUser()->hasPermission('drd.add core entities')) {
      $build[] = [
        '#markup' => $this->t('<p>Just getting started? <a href="@core_add">Add your first core</a> and follow the instructions.</p>',
          ['@core_add' => (new Url('entity.drd_core.add_form'))->toString()]),
      ];
    }

    return $build;
  }

}
