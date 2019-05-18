<?php

namespace Drupal\cloud\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\cloud\Entity\CloudConfigInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Symfony\Component\Routing\Route;

/**
 * Class CloudConfigController.
 *
 *  Returns responses for Cloud config routes.
 */
class CloudConfigController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Render\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs an OperationsController object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The render service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(
    RouteMatchInterface $route_match,
    RendererInterface $renderer,
    DateFormatterInterface $date_formatter) {

    $this->routeMatch = $route_match;
    $this->renderer = $renderer;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('renderer'),
      $container->get('date.formatter')
    );
  }

  /**
   * Displays a Cloud config revision.
   *
   * @param int $cloud_config_revision
   *   The Cloud config  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($cloud_config_revision) {
    $cloud_config = $this->entityManager()->getStorage('cloud_config')->loadRevision($cloud_config_revision);
    $view_builder = $this->entityManager()->getViewBuilder('cloud_config');

    return $view_builder->view($cloud_config);
  }

  /**
   * Page title callback for a Cloud config revision.
   *
   * @param int $cloud_config_revision
   *   The Cloud config  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($cloud_config_revision) {
    $cloud_config = $this->entityManager()->getStorage('cloud_config')->loadRevision($cloud_config_revision);
    return $this->t('Revision of %title from %date', ['%title' => $cloud_config->label(), '%date' => $this->dateFormatter->format($cloud_config->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Cloud config.
   *
   * @param \Drupal\cloud\Entity\CloudConfigInterface $cloud_config
   *   A Cloud config  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(CloudConfigInterface $cloud_config) {
    $account = $this->currentUser();
    $langcode = $cloud_config->language()->getId();
    $langname = $cloud_config->language()->getName();
    $languages = $cloud_config->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $cloud_config_storage = $this->entityManager()->getStorage('cloud_config');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $cloud_config->label()]) : $this->t('Revisions for %title', ['%title' => $cloud_config->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all cloud config revisions") || $account->hasPermission('administer cloud config entities')));
    $delete_permission = (($account->hasPermission("delete all cloud config revisions") || $account->hasPermission('administer cloud config entities')));

    $rows = [];

    $vids = $cloud_config_storage->revisionIds($cloud_config);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\cloud\CloudConfigInterface $revision */
      $revision = $cloud_config_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $cloud_config->getRevisionId()) {
          $link = $this->l($date, new Url('entity.cloud_config.revision', ['cloud_config' => $cloud_config->id(), 'cloud_config_revision' => $vid]));
        }
        else {
          $link = $cloud_config->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
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
              Url::fromRoute('entity.cloud_config.translation_revert', [
                'cloud_config' => $cloud_config->id(),
                'cloud_config_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.cloud_config.revision_revert', ['cloud_config' => $cloud_config->id(), 'cloud_config_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.cloud_config.revision_delete', ['cloud_config' => $cloud_config->id(), 'cloud_config_revision' => $vid]),
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

    $build['cloud_config_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

  /**
   * Checks user access for a specific request based on the cloud context.
   *
   * Supports AND and OR access checks, similar to _permission definition in
   * *.routing.yml.
   *
   * @param string $cloud_context
   *   Cloud context to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Symfony\Component\Routing\Route $route
   *   The route object.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function access($cloud_context, AccountInterface $account, Route $route) {
    $perm = $route->getOption('perm');
    if (strpos($perm, '+') == FALSE && strpos($perm, ',') == FALSE) {
      return AccessResult::allowedIfHasPermissions($account, [
        $perm,
        'view ' . $cloud_context,
      ]);
    }
    else {
      if (!AccessResult::allowedIfHasPermission($account, 'view ' . $cloud_context)->isAllowed()) {
        // Bail if viewing a certain region is not allowed.
        return AccessResult::neutral();
      }
      // Allow to conjunct the permissions with OR ('+') or AND (',').
      $split = explode(',', $perm);
      // Support AND permission check.
      if (count($split) > 1) {
        return AccessResult::allowedIfHasPermissions($account, $split, 'AND');
      }
      else {
        $split = explode('+', $perm);
        return AccessResult::allowedIfHasPermissions($account, $split, 'OR');
      }
    }
  }

}
