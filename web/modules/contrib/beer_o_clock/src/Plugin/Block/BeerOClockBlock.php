<?php

namespace Drupal\beer_o_clock\Plugin\Block;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\beer_o_clock\CheckController;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResult;


/**
* Provides a simple block.
*
* @Block(
*   id = "beer_o_clock_block",
*   admin_label = @Translation("Beer O'Clock Block")
* )
*/
class BeerOClockBlock extends BlockBase {

  /**
   * Implements \Drupal\Block\BlockBase::BlockBuild().
   *
   * @throws \Exception
   */
  public function build() {
    $config = \Drupal::config('beer_o_clock.settings');

    return array(
      '#cache' => array(
        'max-age' => 0,
      ),
      '#theme' => 'beer_o_clock_block',
      '#message' => $config->get('message'),
      '#not_message' => $config->get('not_message'),
      '#display' => $config->get('display'),
      '#isItBeerOClock' => CheckController::isItBeerOClock(),
      '#attached' => array(
        'library' => ['beer_o_clock/countdown'],
        'drupalSettings' => array(
          'beer_o_clock' => array(
            'timer' => CheckController::whenIsItNextBeerOClock(),
            'percentage_full' => CheckController::howMuchBeerWeGot(),
            'day' => $config->get('day'),
            'hour' => $config->get('hour'),
            'duration' => $config->get('duration'),
          )
        )
      ),
    );
  }

  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access beer oclock');
  }

}
