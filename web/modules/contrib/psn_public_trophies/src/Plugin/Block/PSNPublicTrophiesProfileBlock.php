<?php

namespace Drupal\psn_public_trophies\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\psn_public_trophies\Component\DrupalPSNPublicTrophies;

/**
 * Provides a 'PSNPublicTrophiesProfileBlock' Block.
 *
 * @Block(
 *   id = "psn_public_trophies_profile_block",
 *   admin_label = @Translation("PSN Public Trophies Profile Block"),
 *   category = @Translation("PSN Public Trophies"),
 * )
 */
class PSNPublicTrophiesProfileBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    $psn_public_trophies = new DrupalPSNPublicTrophies();

    $profile = $psn_public_trophies->getUserMe();
    $progress_html_arr = [
      '#theme' => 'progress_bar',
      '#percent' => $profile->profile->trophySummary->progress,
    ];

    $trophies = (array) $profile->profile->trophySummary->earnedTrophies;
    $trophies_html_arr = [
      '#theme' => 'psn_public_trophies_trophies_items',
      '#trophies' => $trophies,
    ];

    $game_trophies = $psn_public_trophies->getTrophy()->GetMyTrophies();
    $game_trophies_arr = [];
    foreach ($game_trophies->trophyTitles as $item) {
      $game_trophies_arr[] = [
        'item' => $item,
        'progress' => [
          '#theme' => 'progress_bar',
          '#percent' => $item->fromUser->progress,
        ],
        'trophies' => [
          '#theme' => 'psn_public_trophies_trophies_items',
          '#trophies' => (array) $item->fromUser->earnedTrophies,
        ],
      ];
    }
    $game_trophies_html_arr = [
      '#theme' => 'psn_public_trophies_trophies',
      '#trophies' => $game_trophies_arr,
      '#rendered' => ['game_trophies' => $game_trophies_arr],
    ];

    return [
      '#theme' => 'psn_public_trophies_profile',
      '#profile' => $profile->profile,
      '#trophies' => $game_trophies,
      '#rendered' => [
        'progress' => $progress_html_arr,
        'trophies' => $trophies_html_arr,
        'game_trophies' => $game_trophies_html_arr,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    return $form;
  }

}
