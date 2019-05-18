<?php

namespace Drupal\particlesjs_example\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block with a Particles.JS animation.
 *
 * @Block(
 *   id = "particlesjsexample",
 *   admin_label = @Translation("ParticlesJsExampleBlock"),
 *   category = @Translation("Theme"),
 * )
 */
class ParticlesjsexampleBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Markup.
    $markup = '<div id="particles"></div>';

    return array(
      '#markup' => $markup,
      '#attached' => array(
        'library' => array(
          'particlesjs/particlesjs',
          'particlesjs_example/particlesjs_example',
        ),
      ),
    );
  }

}
