<?php

namespace Drupal\decoupled_quiz\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\decoupled_quiz\Entity\AnswerInterface;

/**
 * Class AnswerController.
 *
 *  Returns responses for Answer routes.
 */
class AnswerController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Answer  revision.
   *
   * @param int $answer_revision
   *   The Answer  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($answer_revision) {
    $answer = $this->entityManager()->getStorage('answer')->loadRevision($answer_revision);
    $view_builder = $this->entityManager()->getViewBuilder('answer');

    return $view_builder->view($answer);
  }

  /**
   * Page title callback for a Answer  revision.
   *
   * @param int $answer_revision
   *   The Answer  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($answer_revision) {
    $answer = $this->entityManager()->getStorage('answer')->loadRevision($answer_revision);
    return $this->t('Revision of %title from %date', ['%title' => $answer->label(), '%date' => format_date($answer->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Answer .
   *
   * @param \Drupal\decoupled_quiz\Entity\AnswerInterface $answer
   *   A Answer  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(AnswerInterface $answer) {
    $account = $this->currentUser();
    $langcode = $answer->language()->getId();
    $langname = $answer->language()->getName();
    $languages = $answer->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $answer_storage = $this->entityManager()->getStorage('answer');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $answer->label()]) : $this->t('Revisions for %title', ['%title' => $answer->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all answer revisions") || $account->hasPermission('administer answer entities')));
    $delete_permission = (($account->hasPermission("delete all answer revisions") || $account->hasPermission('administer answer entities')));

    $rows = [];

    $vids = $answer_storage->revisionIds($answer);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\decoupled_quiz\AnswerInterface $revision */
      $revision = $answer_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $answer->getRevisionId()) {
          $link = $this->l($date, new Url('entity.answer.revision', ['answer' => $answer->id(), 'answer_revision' => $vid]));
        }
        else {
          $link = $answer->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.answer.translation_revert', [
                'answer' => $answer->id(),
                'answer_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.answer.revision_revert', ['answer' => $answer->id(), 'answer_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.answer.revision_delete', ['answer' => $answer->id(), 'answer_revision' => $vid]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['answer_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
