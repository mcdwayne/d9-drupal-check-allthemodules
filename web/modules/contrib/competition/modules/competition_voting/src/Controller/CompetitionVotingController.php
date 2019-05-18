<?php

namespace Drupal\competition_voting\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\competition\CompetitionEntryInterface;
use Drupal\competition\CompetitionInterface;
use Drupal\competition\Entity\Competition;
use Drupal\competition\CompetitionJudgingSetup;
use Drupal\competition_voting\CompetitionVoting;

/**
 * Class CompetitionVotingController.
 *
 * @package Drupal\competition_voting\Controller
 */
class CompetitionVotingController extends ControllerBase {

  /**
   * The competition entry storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The competition entry judging service.
   *
   * @var \Drupal\competition\CompetitionJudgingSetup
   */
  protected $competitionJudging;

  /**
   * The competition voting service.
   *
   * @var \Drupal\competition_voting\CompetitionVoting
   */
  protected $competitionVoting;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityStorageInterface $storage, DateFormatterInterface $date_formatter, CompetitionJudgingSetup $competition_judging, CompetitionVoting $competition_voting) {

    $this->storage = $storage;
    $this->dateFormatter = $date_formatter;
    $this->competitionJudging = $competition_judging;
    $this->competitionVoting = $competition_voting;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('entity_type.manager')->getStorage('competition_entry'),
      $container->get('date.formatter'),
      $container->get('competition.judging_setup'),
      $container->get('competition.voting')
    );

  }

  /**
   * Vote callback.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   * @param \Drupal\competition\Entity\Competition $competition
   *   The custom bundle/type being added.
   *
   * @return array
   *   Render array
   */
  public function vote(Request $request, Competition $competition) {

    // Get judging data for this competition and active voting round.
    $judging = $competition->getJudging();
    $round = $judging->rounds[$judging->active_round];

    // Quick checks for when page should be unavailable.
    // 1. Competition is not in 'offline' mode.
    // 2. No active judging around
    // 3. Active judging round is not a voting round.
    if ($competition->getStatus() != CompetitionInterface::STATUS_CLOSED
      || !$judging->enabled
      || !$judging->active_round
      || $round['round_type'] != 'voting') {

      if (!empty($round['voting_inactive_redirect_path'])) {
        return new RedirectResponse(Url::fromUserInput($round['voting_inactive_redirect_path'])->toString());
      }
      else {
        throw new NotFoundHttpException();
      }

    }

    // Get list of entries elligible for voting.
    $ceids = $this->competitionJudging
      ->filterJudgingEntries($competition->id(), [
        'round_id' => $judging->active_round,
      ]);

    // Load competition entry entities.
    $entries = $this->storage
      ->loadMultiple($ceids);

    // Shuffle entries.
    shuffle($entries);

    // Load competition entry views.
    $build = $this->entityManager()
      ->getViewBuilder('competition_entry')
      ->viewMultiple($entries, 'voting');

    $build['#theme'] = 'competition_voting';
    $build['#cache'] = ['max-age' => 0];
    $build['#voting_criteria_description'] = [
      '#markup' => $round['criteria_description'],
    ];
    $build['#voting_thanks'] = [
      '#markup' => $round['voting_thanks'],
    ];
    $build['#voting_legal_message'] = [
      '#markup' => $round['voting_legal_message'],
    ];

    return $build;

  }

  /**
   * View vote records for a competition entry.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   * @param \Drupal\competition\CompetitionEntryInterface $competition_entry
   *   The custom bundle/type being added.
   *
   * @return array
   *   Render array
   */
  public function viewVotes(Request $request, CompetitionEntryInterface $competition_entry) {

    $build['title'] = [
      '#markup' => '<h1>' . $this->t('Votes for competition entry @ceid', [
        '@ceid' => $competition_entry->id(),
      ]) . '</h1>',
    ];

    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Source ID'),
        $this->t('Source IP'),
        $this->t('Timestamp'),
        $this->t('Round'),
      ],
      '#empty' => $this->t('There are no votes yet.'),
    ];

    $params = [
      'ceid' => $competition_entry->id(),
    ];
    $votes = $this->competitionVoting->getVotes($params);

    foreach ($votes as $id => $vote) {

      // Some table columns containing raw markup.
      $build['table'][$id]['source_id'] = [
        '#plain_text' => $vote->source_id,
      ];
      $build['table'][$id]['source_ip'] = [
        '#plain_text' => $vote->source_ip,
      ];
      $build['table'][$id]['timestamp'] = [
        '#plain_text' => $this->dateFormatter->format($vote->timestamp, 'medium'),
      ];
      $build['table'][$id]['round_id'] = [
        '#plain_text' => $vote->round_id,
      ];
    }

    return $build;

  }

}
