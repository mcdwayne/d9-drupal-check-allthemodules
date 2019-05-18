<?php

namespace Drupal\contest\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\contest\ContestHelper;
use Drupal\contest\ContestInterface;
use Drupal\contest\ContestStorage;
use Drupal\contest\ContestUser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for contest module routes.
 */
class ContestController extends ControllerBase {
  protected $cfgStore;
  protected $contestStorage;
  protected $dateFrmt;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $contestStorage
   *   The contest storage dependency injection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $cfgStore
   *   The config factory dependency injection.
   * @param \Drupal\Core\Datetime\DateFormatter $dateFrmt
   *   The date formater dependency injection.
   */
  public function __construct(EntityStorageInterface $contestStorage, ConfigFactoryInterface $cfgStore, DateFormatter $dateFrmt) {
    $this->cfgStore = $cfgStore;
    $this->contestStorage = $contestStorage;
    $this->dateFrmt = $dateFrmt;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager')->getStorage('contest'), $container->get('config.factory'), $container->get('date.formatter'));
  }

  /**
   * Admin page callback.
   *
   * @param \Drupal\contest\ContestInterface $contest
   *   The contest entity.
   *
   * @return array
   *   An array containing the admin page theme function.
   */
  public function contestAdmin(ContestInterface $contest) {
    return ['#theme' => 'contest_admin'];
  }

  /**
   * Clear winner(s) of a contest.
   *
   * @param int $cid
   *   The contest ID.
   * @param int $uid
   *   The user's ID.
   *
   * @return Symfony\Component\HttpFoundation\RedirectResponse
   *   Reidrect back to the admin page.
   */
  public function contestClearWinners($cid, $uid = NULL) {
    if (ContestStorage::getPublished($cid)) {
      drupal_set_message($this->t('You must unpublish contest winners before clearing a winner.'), 'warning');
    }
    elseif ($cid && $uid) {
      $this->contestStorage->clearWinner($cid, $uid);
    }
    elseif ($cid) {
      $this->contestStorage->clearWinners($cid);
    }
    return new RedirectResponse("/contest/$cid/admin");
  }

  /**
   * Export a complete list of contest entries.
   *
   * @param \Drupal\contest\ContestInterface $contest
   *   The contest entity.
   */
  public function contestExportEntries(ContestInterface $contest) {
    $csv = ContestHelper::csvHeader();
    $file_name = "contest_entries_{$contest->id->value}_" . $this->dateFrmt->format(REQUEST_TIME, 'custom', 'YmdHis') . '.csv';

    foreach ($this->contestStorage->getEntries($contest->id->value) as $row) {
      $usr = new ContestUser($row->uid);

      if ($usr->uid) {
        $csv .= ContestHelper::toCsv($usr);
      }
    }
    ContestHelper::downloadFile($file_name, $csv);
  }

  /**
   * Export a list of unique entrants.
   *
   * @param \Drupal\contest\ContestInterface $contest
   *   The contest entity.
   */
  public function contestExportUnique(ContestInterface $contest) {
    $csv = ContestHelper::csvHeader();
    $file_name = "contest_unique_{$contest->id->value}_" . $this->dateFrmt->format(REQUEST_TIME, 'custom', 'YmdHis') . '.csv';

    foreach ($this->contestStorage->getUniqueEntries($contest->id->value) as $row) {
      $usr = new ContestUser($row->uid);

      if ($usr->uid) {
        $csv .= ContestHelper::toCsv($usr);
      }
    }
    ContestHelper::downloadFile($file_name, $csv);
  }

  /**
   * Clear winner(s) of a contest.
   *
   * @param int $cid
   *   The contest ID.
   * @param int $uid
   *   The user's ID.
   *
   * @return Symfony\Component\HttpFoundation\RedirectResponse
   *   Reidrect back to the admin page.
   */
  public function contestPickWinner($cid, $uid = NULL) {
    $dq_days = $this->cfgStore->get('contest.config')->get('dq_days');
    $wids = [0];

    // We don't allow contest results to be edited while they are published.
    $row = $this->contestStorage->winnerPreSelect($cid);

    $dq = $row->end - ($dq_days * ContestStorage::DAY);

    if ($row->publish_winners) {
      drupal_set_message($this->t('You must unpublish a contest before editing results.'), 'warning');
      return new RedirectResponse("/contest/$cid/admin");
    }
    // We don't allow the winner to be picked when the contest is still open.
    if (REQUEST_TIME < $row->end) {
      drupal_set_message($this->t("You can't select a contest winner before the contest ends."), 'warning');
      return new RedirectResponse("/contest/$cid/admin");
    }
    // Check to see if there are any more winners allowed.
    $winners = $this->contestStorage->getWinnerCount($cid);

    if ($winners >= $row->places) {
      drupal_set_message(format_plural($row->places, 'The contest allows one winner.', 'The contest allows @count places.'), 'warning');
      drupal_set_message(format_plural($row->places, 'Currently there is one.', 'Currently there are @count.'), 'warning');
      return new RedirectResponse("/contest/$cid/admin");
    }
    // CSV of DQ'd uids to be used in a "IN" sql list.
    $rows = $this->contestStorage->getDqs($dq);

    foreach ($rows as $row) {
      if ($uid && $row->uid == $uid) {
        drupal_set_message(format_plural($dq_days, 'This person has already won something in the last day.', 'This person has already won something in the last @count days.'), 'warning');
        return new RedirectResponse("/contest/$cid/admin");
      }
      $wids[] = $row->uid;
    }
    // Select the winner randomly from the qualifying entrants.
    if ($uid) {
      $row = $this->contestStorage->getRandomUserEntry($cid, $uid);
    }
    else {
      $row = $this->contestStorage->getRandomEntry($cid, $wids);
    }
    // If we have a winner get the next place and update the winning entry.
    if ($row->uid) {
      $place = 1 + $this->contestStorage->getMaxPlace($cid);
      $this->contestStorage->setWinner($cid, $row->uid, $place, $row->created);
    }
    return new RedirectResponse("/contest/$cid/admin");
  }

  /**
   * The contest publish winners callback.
   *
   * @param \Drupal\contest\ContestInterface $contest
   *   The contest entity.
   *
   * @return Symfony\Component\HttpFoundation\RedirectResponse
   *   Reidrect back to the admin page.
   */
  public function contestPublishWinners(ContestInterface $contest) {
    $this->contestStorage->publishWinners($contest->id->value);

    return new RedirectResponse("/contest/{$contest->id->value}/admin");
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\contest\ContestInterface $contest
   *   The contest entity.
   *
   * @return string
   *   The contest label.
   */
  public function contestTitle(ContestInterface $contest) {
    return Xss::filter($contest->label());
  }

  /**
   * The contest publish winners callback.
   *
   * @param \Drupal\contest\ContestInterface $contest
   *   The contest entity.
   *
   * @return Symfony\Component\HttpFoundation\RedirectResponse
   *   Reidrect back to the admin page.
   */
  public function contestUnpublishWinners(ContestInterface $contest) {
    $this->contestStorage->unpublishWinners($contest->id->value);

    return new RedirectResponse("/contest/{$contest->id->value}/admin");
  }

}
