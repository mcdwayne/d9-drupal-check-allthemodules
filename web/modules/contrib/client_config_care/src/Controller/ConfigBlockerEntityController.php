<?php

namespace Drupal\client_config_care\Controller;

use Drupal\client_config_care\Entity\ConfigBlockerEntityInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class ConfigBlockerEntityController.
 *
 *  Returns responses for Config blocker entity routes.
 */
class ConfigBlockerEntityController extends ControllerBase implements ContainerInjectionInterface {

  public function revisionOverview(ConfigBlockerEntityInterface $config_blocker_entity) {
    $langcode = $config_blocker_entity->language()->getId();
    $langname = $config_blocker_entity->language()->getName();
    $languages = $config_blocker_entity->getTranslationLanguages();
    $has_translations = (\count($languages) > 1);
    $config_blocker_entity_storage = $this->entityManager()->getStorage('config_blocker_entity');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $config_blocker_entity->label()]) : $this->t('Revisions for %title', ['%title' => $config_blocker_entity->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $rows = [];

    $vids = $config_blocker_entity_storage->revisionIds($config_blocker_entity);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\client_config_care\Entity\ConfigBlockerEntity $revision */
      $revision = $config_blocker_entity_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'long');

        $row = [];
        $column = [
          'data' => [
            '#type'     => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context'  => [
              'date'     => $date,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message'  => [
                '#markup'       => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
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
          $row[] = [];
        }

        $rows[] = $row;
      }
    }

    $build['config_blocker_entity_revisions_table'] = [
      '#theme'  => 'table',
      '#rows'   => $rows,
      '#header' => $header,
    ];

    return $build;
  }

  public function redirectToEntityView() {
    return new RedirectResponse('/admin/structure/config_blocker_entity');
  }

}
