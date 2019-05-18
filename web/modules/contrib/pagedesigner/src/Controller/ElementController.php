<?php

namespace Drupal\pagedesigner\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\pagedesigner\Entity\ElementInterface;

/**
 * Class ElementController.
 *
 *  Returns responses for Pagedesigner Element routes.
 */
class ElementController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Pagedesigner Element  revision.
   *
   * @param int $pagedesigner_element_revision
   *   The Pagedesigner Element  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($pagedesigner_element_revision) {
    $pagedesigner_element = $this->entityManager()->getStorage('pagedesigner_element')->loadRevision($pagedesigner_element_revision);
    $view_builder = $this->entityManager()->getViewBuilder('pagedesigner_element');

    return $view_builder->view($pagedesigner_element);
  }

  /**
   * Page title callback for a Pagedesigner Element  revision.
   *
   * @param int $pagedesigner_element_revision
   *   The Pagedesigner Element  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($pagedesigner_element_revision) {
    $pagedesigner_element = $this->entityManager()->getStorage('pagedesigner_element')->loadRevision($pagedesigner_element_revision);
    return $this->t('Revision of %title from %date', ['%title' => $pagedesigner_element->label(), '%date' => format_date($pagedesigner_element->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Pagedesigner Element .
   *
   * @param \Drupal\pagedesigner\Entity\ElementInterface $pagedesigner_element
   *   A Pagedesigner Element  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(ElementInterface $pagedesigner_element) {
    $account = $this->currentUser();
    $langcode = $pagedesigner_element->language()->getId();
    $langname = $pagedesigner_element->language()->getName();
    $languages = $pagedesigner_element->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $pagedesigner_element_storage = $this->entityManager()->getStorage('pagedesigner_element');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $pagedesigner_element->label()]) : $this->t('Revisions for %title', ['%title' => $pagedesigner_element->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all pagedesigner element revisions") || $account->hasPermission('administer pagedesigner element entities')));
    $delete_permission = (($account->hasPermission("delete all pagedesigner element revisions") || $account->hasPermission('administer pagedesigner element entities')));

    $rows = [];

    $vids = $pagedesigner_element_storage->revisionIds($pagedesigner_element);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\pagedesigner\ElementInterface $revision */
      $revision = $pagedesigner_element_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $pagedesigner_element->getRevisionId()) {
          $link = $this->l($date, new Url('entity.pagedesigner_element.revision', ['pagedesigner_element' => $pagedesigner_element->id(), 'pagedesigner_element_revision' => $vid]));
        }
        else {
          $link = $pagedesigner_element->link($date);
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
              Url::fromRoute('entity.pagedesigner_element.translation_revert', ['pagedesigner_element' => $pagedesigner_element->id(), 'pagedesigner_element_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.pagedesigner_element.revision_revert', ['pagedesigner_element' => $pagedesigner_element->id(), 'pagedesigner_element_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.pagedesigner_element.revision_delete', ['pagedesigner_element' => $pagedesigner_element->id(), 'pagedesigner_element_revision' => $vid]),
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

    $build['pagedesigner_element_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
