<?php

/**
 * @file
 * Contains \Drupal\rocketship\Plugin\Block\TeamBlock.
 */

namespace Drupal\rocketship\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Component\Utility\Html;

/**
 * Provides a team display block.
 *
 * @Block(
 *   id = "rocketship_team_block",
 *   admin_label = @Translation("Rocketship issue team"),
 * )
 */
class TeamBlock extends BlockBase {

  /**
   * {@inheritdoc@}
   */
  public function build() {
    $output = '';
    $config = \Drupal::config('rocketship.settings');

    // Include username cloud if the sprint tag is displayed.
    $participant_stats = $config->get('participant_stats');
    if (!empty($participant_stats)) {
      $lead_user = $config->get('lead_user');
      $participant_uids = $config->get('participant_uids');
      $participant_recent = $config->get('participant_recent');

      // Remove lead user and some users to avoid skewing the stats.
      if (!empty($lead_user)) {
        unset($participant_stats[$lead_user]);
      }
      unset($participant_stats['System Message']);
      unset($participant_stats['Abandoned Projects']);

      $usercloud = array();
      $max = max(array_values($participant_stats));
      $steps = array(1, 2, 3, 5, 7, 10);
      foreach ($steps as $i => $step) {
        foreach ($participant_stats as $name => $count) {
          if (($step == 10) || ($count <= $step * ($max/10))) {
            $class = 'rocketship-cloud-' . ($i+1);
            if (in_array($participant_uids[$name], $participant_recent)) {
              $class .= ' rocketship-cloud-recent';
            }
            $usercloud[$name] = '<span class="' . $class . '"><a href="https://drupal.org/user/' . $participant_uids[$name] . '">' . Html::escape($name) . '</a></span>';
            unset($participant_stats[$name]);
          }
        }
      }
      // Sort by username in a case insensitive fashion.
      uksort($usercloud, "strnatcasecmp");
      $note = '<p>' . $this->t('Based on participation in all @tag issues (font size) using a quasi-logarithmic scale and on recent activity (boldness).', array('@tag' => $config->get('master_tag')));
      if (!empty($lead_user)) {
        $note .= ' ' .  $this->t('Excludes the initiative lead, @lead.', array('@lead' => $lead_user));
      }
      $note .= '</p>';
      $output .= '<div class="rocketship-cloud-wrapper"><div class="rocketship-cloud">' . join(' ', $usercloud) . '</div>' . $note . '</div>';
    }

    if ($output) {
      $attached = array(
        'library' => array('rocketship/cloud'),
      );

      return array(
        'content' =>
          array(
            '#markup' => $output,
            '#attached' => $attached,
          ),
      );
    }
    return array();
  }

}