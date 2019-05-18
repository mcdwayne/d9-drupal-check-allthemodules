<?php

namespace Drupal\facture\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\facture\Entity\ClientInterface;

/**
 * Class ClientController.
 *
 *  Returns responses for Client routes.
 */
class ClientController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Client  revision.
   *
   * @param int $client_revision
   *   The Client  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($client_revision) {
    $client = $this->entityManager()->getStorage('client')->loadRevision($client_revision);
    $view_builder = $this->entityManager()->getViewBuilder('client');

    return $view_builder->view($client);
  }

  /**
   * Page title callback for a Client  revision.
   *
   * @param int $client_revision
   *   The Client  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($client_revision) {
    $client = $this->entityManager()->getStorage('client')->loadRevision($client_revision);
    return $this->t('Revision of %title from %date', ['%title' => $client->label(), '%date' => format_date($client->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Client .
   *
   * @param \Drupal\facture\Entity\ClientInterface $client
   *   A Client  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(ClientInterface $client) {
    $account = $this->currentUser();
    $langcode = $client->language()->getId();
    $langname = $client->language()->getName();
    $languages = $client->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $client_storage = $this->entityManager()->getStorage('client');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $client->label()]) : $this->t('Revisions for %title', ['%title' => $client->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all client revisions") || $account->hasPermission('administer client entities')));
    $delete_permission = (($account->hasPermission("delete all client revisions") || $account->hasPermission('administer client entities')));

    $rows = [];

    $vids = $client_storage->revisionIds($client);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\facture\ClientInterface $revision */
      $revision = $client_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $client->getRevisionId()) {
          $link = $this->l($date, new Url('entity.client.revision', ['client' => $client->id(), 'client_revision' => $vid]));
        }
        else {
          $link = $client->link($date);
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
              Url::fromRoute('entity.client.translation_revert', ['client' => $client->id(), 'client_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.client.revision_revert', ['client' => $client->id(), 'client_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.client.revision_delete', ['client' => $client->id(), 'client_revision' => $vid]),
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

    $build['client_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
