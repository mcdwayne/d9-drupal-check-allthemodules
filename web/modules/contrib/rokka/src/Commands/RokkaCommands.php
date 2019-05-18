<?php

namespace Drupal\rokka\Commands;

use Drupal\image\Entity\ImageStyle;
use Drupal\rokka\Entity\RokkaStack;
use Drush\Commands\DrushCommands;

/**
 *
 * In addition to a commandfile like this one, you need to add a drush.services.yml
 * in root of your module.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class RokkaCommands extends DrushCommands {

  /**
   * Migrate existing imagestyles to rokka stacks.
   *
   * @command rokka:migrate-imagestyles
   * @option force
   * @aliases rim, rokka-mim
   */
  public function migrateImageStyles($options = ['force' => FALSE]) {
    $this->output()->writeln('Migrating local image styles to rokka.io stacks.');
    $styles = ImageStyle::loadMultiple();
    $stacks = RokkaStack::loadMultiple();

    // Find all and create all missing stacks on rokka.io
    foreach ($styles as $style) {
      /** @var ImageStyle $style */
      if (empty($stacks[$style->getName()]) || $options['force']) {
        // Create stack
        try {
          rokka_image_style_presave($style);
          $this->logger()
            ->success(dt('Image style !style_name stack created on rokka.io', ['!style_name' => $style->getName()]));
        } catch (\Exception $ex) {
          $this->logger()->error(
            dt('Image style !style_name stack failed to save on rokka.io', ['!style_name' => $style->getName()])
          );
        }
      }
    }
    $this->output()->writeln('Image styles migration completed.');
  }
}