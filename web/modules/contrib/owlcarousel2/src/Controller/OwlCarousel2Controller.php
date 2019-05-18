<?php

namespace Drupal\owlcarousel2\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\owlcarousel2\Entity\OwlCarousel2Interface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class OwlCarousel2Controller.
 *
 *  Returns responses for OwlCarousel2 routes.
 */
class OwlCarousel2Controller extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public function __construct(ContainerInterface $container) {
    $this->dateFormatter = $container->get('date.formatter');
    $this->renderer = $container->get('renderer');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container);
  }

  /**
   * Displays a OwlCarousel2  revision.
   *
   * @param int $owlcarousel2_revision
   *   The OwlCarousel2  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($owlcarousel2_revision) {
    $owlcarousel2 = $this->entityManager()
      ->getStorage('owlcarousel2')
      ->loadRevision($owlcarousel2_revision);
    $view_builder = $this->entityManager()->getViewBuilder('owlcarousel2');

    return $view_builder->view($owlcarousel2);
  }

  /**
   * Page title callback for a OwlCarousel2  revision.
   *
   * @param int $owlcarousel2_revision
   *   The OwlCarousel2  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($owlcarousel2_revision) {
    $owlcarousel2 = $this->entityManager()
      ->getStorage('owlcarousel2')
      ->loadRevision($owlcarousel2_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $owlcarousel2->label(),
      '%date'  => $this->dateFormatter->format($owlcarousel2->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a OwlCarousel2 .
   *
   * @param \Drupal\owlcarousel2\Entity\OwlCarousel2Interface $owlcarousel2
   *   A OwlCarousel2  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(OwlCarousel2Interface $owlcarousel2) {
    $account              = $this->currentUser();
    $langcode             = $owlcarousel2->language()->getId();
    $langname             = $owlcarousel2->language()->getName();
    $languages            = $owlcarousel2->getTranslationLanguages();
    $has_translations     = (count($languages) > 1);
    $owlcarousel2_storage = $this->entityManager()->getStorage('owlcarousel2');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', [
      '@langname' => $langname,
      '%title'    => $owlcarousel2->label(),
    ]) : $this->t('Revisions for %title', ['%title' => $owlcarousel2->label()]);
    $header          = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all owlcarousel2 revisions") || $account->hasPermission('administer owlcarousel2 entities')));
    $delete_permission = (($account->hasPermission("delete all owlcarousel2 revisions") || $account->hasPermission('administer owlcarousel2 entities')));

    $rows = [];

    $vids = $owlcarousel2_storage->revisionIds($owlcarousel2);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\owlcarousel2\OwlCarousel2Interface $revision */
      $revision = $owlcarousel2_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)
        ->isRevisionTranslationAffected()
      ) {
        $username = [
          '#theme'   => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $owlcarousel2->getRevisionId()) {
          $link = $this->l($date, new Url('entity.owlcarousel2.revision', [
            'owlcarousel2'          => $owlcarousel2->id(),
            'owlcarousel2_revision' => $vid,
          ]));
        }
        else {
          $link = $owlcarousel2->link($date);
        }

        $row    = [];
        $column = [
          'data' => [
            '#type'     => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context'  => [
              'date'     => $link,
              'username' => $this->renderer->renderPlain($username),
              'message'  => [
                '#markup'       => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[]  = $column;

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
              'url'   => $has_translations ?
              Url::fromRoute('entity.owlcarousel2.translation_revert',
                  [
                    'owlcarousel2'          => $owlcarousel2->id(),
                    'owlcarousel2_revision' => $vid,
                    'langcode'              => $langcode,
                  ]) :
              Url::fromRoute('entity.owlcarousel2.revision_revert',
                  [
                    'owlcarousel2'          => $owlcarousel2->id(),
                    'owlcarousel2_revision' => $vid,
                  ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url'   => Url::fromRoute('entity.owlcarousel2.revision_delete', [
                'owlcarousel2'          => $owlcarousel2->id(),
                'owlcarousel2_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type'  => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['owlcarousel2_revisions_table'] = [
      '#theme'  => 'table',
      '#rows'   => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
