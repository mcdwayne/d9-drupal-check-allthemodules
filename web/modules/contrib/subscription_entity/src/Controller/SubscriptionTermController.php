<?php

namespace Drupal\subscription_entity\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\subscription_entity\Entity\SubscriptionTermInterface;

/**
 * Class SubscriptionTermController.
 *
 *  Returns responses for Subscription Term routes.
 *
 * @package Drupal\subscription_entity\Controller
 */
class SubscriptionTermController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Subscription Term  revision.
   *
   * @param int $subscription_term_revision
   *   The Subscription Term  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($subscription_term_revision) {
    $subscription_term = $this->entityManager()->getStorage('subscription_term')->loadRevision($subscription_term_revision);
    $view_builder = $this->entityManager()->getViewBuilder('subscription_term');

    return $view_builder->view($subscription_term);
  }

  /**
   * Page title callback for a Subscription Term  revision.
   *
   * @param int $subscription_term_revision
   *   The Subscription Term  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($subscription_term_revision) {
    $subscription_term = $this->entityManager()->getStorage('subscription_term')->loadRevision($subscription_term_revision);
    return $this->t('Revision of %title from %date', array('%title' => $subscription_term->label(), '%date' => format_date($subscription_term->getRevisionCreationTime())));
  }

  /**
   * Generates an overview table of older revisions of a Subscription Term .
   *
   * @param \Drupal\subscription_entity\Entity\SubscriptionTermInterface $subscription_term
   *   A Subscription Term  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(SubscriptionTermInterface $subscription_term) {
    $account = $this->currentUser();
    $langcode = $subscription_term->language()->getId();
    $langname = $subscription_term->language()->getName();
    $languages = $subscription_term->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $subscription_term_storage = $this->entityManager()->getStorage('subscription_term');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $subscription_term->label()]) : $this->t('Revisions for %title', ['%title' => $subscription_term->label()]);
    $header = array($this->t('Revision'), $this->t('Operations'));

    $revert_permission = (($account->hasPermission("revert all subscription term revisions") || $account->hasPermission('administer subscription term entities')));
    $delete_permission = (($account->hasPermission("delete all subscription term revisions") || $account->hasPermission('administer subscription term entities')));

    $rows = array();

    $vids = $subscription_term_storage->revisionIds($subscription_term);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\subscription_entity\SubscriptionTermInterface $revision */
      $revision = $subscription_term_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->revision_timestamp->value, 'short');
        if ($vid != $subscription_term->getRevisionId()) {
          $link = $this->l($date, new Url('entity.subscription_term.revision', ['subscription_term' => $subscription_term->id(), 'subscription_term_revision' => $vid]));
        }
        else {
          $link = $subscription_term->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => ['#markup' => $revision->revision_log_message->value, '#allowed_tags' => Xss::getHtmlTagList()],
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
              Url::fromRoute('subscription_term.revision_revert_translation_confirm', [
                'subscription_term' => $subscription_term->id(),
                'subscription_term_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('subscription_term.revision_revert_confirm', ['subscription_term' => $subscription_term->id(), 'subscription_term_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('subscription_term.revision_delete_confirm', ['subscription_term' => $subscription_term->id(), 'subscription_term_revision' => $vid]),
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

    $build['subscription_term_revisions_table'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    );

    return $build;
  }

}
