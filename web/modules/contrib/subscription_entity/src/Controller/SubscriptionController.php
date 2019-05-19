<?php

namespace Drupal\subscription_entity\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\subscription_entity\Entity\SubscriptionInterface;

/**
 * Class SubscriptionController.
 *
 *  Returns responses for Subscription routes.
 *
 * @package Drupal\subscription_entity\Controller
 */
class SubscriptionController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Subscription  revision.
   *
   * @param int $subscription_revision
   *   The Subscription  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($subscription_revision) {
    $subscription = $this->entityManager()->getStorage('subscription')->loadRevision($subscription_revision);
    $view_builder = $this->entityManager()->getViewBuilder('subscription');

    return $view_builder->view($subscription);
  }

  /**
   * Page title callback for a Subscription  revision.
   *
   * @param int $subscription_revision
   *   The Subscription  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($subscription_revision) {
    $subscription = $this->entityManager()->getStorage('subscription')->loadRevision($subscription_revision);
    return $this->t('Revision of %title from %date', array('%title' => $subscription->label(), '%date' => format_date($subscription->getRevisionCreationTime())));
  }

  /**
   * Generates an overview table of older revisions of a Subscription .
   *
   * @param \Drupal\subscription_entity\Entity\SubscriptionInterface $subscription
   *   A Subscription  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(SubscriptionInterface $subscription) {
    $account = $this->currentUser();
    $langcode = $subscription->language()->getId();
    $langname = $subscription->language()->getName();
    $languages = $subscription->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $subscription_storage = $this->entityManager()->getStorage('subscription');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $subscription->label()]) : $this->t('Revisions for %title', ['%title' => $subscription->label()]);
    $header = array($this->t('Revision'), $this->t('Operations'));

    $revert_permission = (($account->hasPermission("revert all subscription revisions") || $account->hasPermission('administer subscription entities')));
    $delete_permission = (($account->hasPermission("delete all subscription revisions") || $account->hasPermission('administer subscription entities')));

    $rows = array();

    $vids = $subscription_storage->revisionIds($subscription);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\subscription_entity\SubscriptionInterface $revision */
      $revision = $subscription_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->revision_timestamp->value, 'short');
        if ($vid != $subscription->getRevisionId()) {
          $link = $this->l($date, new Url('entity.subscription.revision', ['subscription' => $subscription->id(), 'subscription_revision' => $vid]));
        }
        else {
          $link = $subscription->link($date);
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
              'url' => Url::fromRoute('subscription.revision_revert_confirm', ['subscription' => $subscription->id(), 'subscription_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('subscription.revision_delete_confirm', ['subscription' => $subscription->id(), 'subscription_revision' => $vid]),
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

    $build['subscription_revisions_table'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    );

    return $build;
  }

}
