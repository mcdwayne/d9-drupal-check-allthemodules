<?php

namespace Drupal\cmood\Controller;

use Drupal\cmood\Storage\CmoodStorage;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Class CmoodController implements controller class for cmood menu items and.
 *
 * Consist helper functions.
 */
class CmoodController extends ControllerBase {

  /**
   * List content with there moods as a table list.
   *
   * @return string
   *   Table list.
   */
  public static function cmoodNodeMoodListing() {
    $header = [
      ['data' => t('ID'), 'field' => 'id', 'sort' => 'asc'],
      ['data' => t('Node Title'), 'field' => 'title'],
      ['data' => t('Node Mood'), 'field' => 'mood'],
    ];
    $rows = [];
    foreach (CmoodStorage::getCmoodContent($header) as $row) {
      $rows[] = [
        $row->id,
        Unicode::truncate(Html::escape($row->title), 30),
        $row->mood,
      ];
    }
    $table = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return [
      '#type' => 'markup',
      '#markup' => \Drupal::service('renderer')->render($table),
    ];
  }

  /**
   * List rank associated with each cmood word.
   *
   * @return string
   *   Rank list.
   */
  public static function cmoodRankWordListing() {
    $header = [
      ['data' => t('RWID'), 'field' => 'rwid', 'sort' => 'asc'],
      ['data' => t('Word'), 'field' => 'name'],
      ['data' => t('Weight'), 'field' => 'weight'],
      ['data' => t('UID'), 'field' => 'uid'],
      ['data' => t('Edit'), 'field' => 'edit'],
      ['data' => t('Delete'), 'field' => 'delete'],
    ];
    $rows = [];
    foreach (CmoodStorage::getCmoodWordsRank($header) as $row) {
      $url = Url::fromRoute('cmood.edit_rank_word', ['rwid' => $row->rwid]);
      $url_delete = Url::fromRoute('cmood.rank_word_delete', ['rwid' => $row->rwid]);
      $rows[] = [
        $row->rwid,
        Html::escape($row->name),
        $row->weight,
        $row->uid,
        \Drupal::l(t('Edit'), $url),
        \Drupal::l(t('Delete'), $url_delete),
      ];
    }
    $table = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return [
      '#type' => 'markup',
      '#markup' => \Drupal::service('renderer')->render($table),
    ];
  }

  /**
   * List all added mood words.
   *
   * @return string
   *   Mod words list.
   */
  public static function cmoodMoodWordListing() {
    $header = [
      ['data' => t('WID'), 'field' => 'wid', 'sort' => 'asc'],
      ['data' => t('Word'), 'field' => 'name'],
      ['data' => t('Weight'), 'field' => 'weight'],
      ['data' => t('UID'), 'field' => 'uid'],
      ['data' => t('Edit'), 'field' => 'Edit'],
      ['data' => t('Delete'), 'field' => 'Delete'],
    ];
    $rows = [];
    foreach (CmoodStorage::getCmoodWords($header) as $row) {
      $url = Url::fromRoute('cmood.edit_mood_word', ['wid' => $row->wid]);
      $url_delete = Url::fromRoute('cmood.mood_word_delete', ['wid' => $row->wid]);
      $rows[] = [
        $row->wid,
        Html::escape($row->name),
        $row->weight,
        $row->uid,
        \Drupal::l(t('Edit'), $url),
        \Drupal::l(t('Delete'), $url_delete),
      ];
    }
    $table = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return [
      '#type' => 'markup',
      '#markup' => \Drupal::service('renderer')->render($table),
    ];
  }

  /**
   * Gets edit form for Edit mood word.
   *
   * @param int $wid
   *   Mood word id.
   */
  public function cmoodEditMoodWord($wid) {
    $form = \Drupal::formBuilder()
      ->getForm('Drupal\cmood\Form\CmoodAddMoodWord', $wid);

    return $form;
  }

  /**
   * Gets edit form for Edit rank word.
   *
   * @param int $rwid
   *   Rank word id.
   */
  public function cmoodEditRankWord($rwid) {
    $form = \Drupal::formBuilder()
      ->getForm('Drupal\cmood\Form\CmoodAddRankWord', $rwid);

    return $form;
  }

  /**
   * Gets delete form for mood word.
   *
   * @param int $wid
   *   Mood word id.
   */
  public function cmoodDeleteMoodWord($wid) {
    $form = \Drupal::formBuilder()
      ->getForm('Drupal\cmood\Form\DeleteMoodWordConfirm', $wid);

    return $form;
  }

  /**
   * Gets delete form for rank word.
   *
   * @param int $rwid
   *   Rank word id.
   */
  public function cmoodDeleteRankWord($rwid) {
    $form = \Drupal::formBuilder()
      ->getForm('Drupal\cmood\Form\DeleteMoodRankConfirm', $rwid);

    return $form;
  }

}
