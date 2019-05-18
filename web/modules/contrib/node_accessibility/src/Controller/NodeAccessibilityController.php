<?php

namespace Drupal\node_accessibility\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\node\NodeInterface;
use Drupal\node\Controller\NodeController;
use Drupal\node_accessibility\ProblemsStorage;
use Drupal\quail_api\QuailApiSettings;

/**
 * Base class for controllers that return responses on entity revision routes.
 */
class NodeAccessibilityController extends NodeController {

  /**
   * Generates an overview table of older revisions of a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(NodeInterface $node) {
    $account = $this->currentUser();
    $langcode = $node->language()->getId();
    $langname = $node->language()->getName();
    $languages = $node->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $node_storage = $this->entityManager()->getStorage('node');
    $type = $node->getType();

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $node->label()]) : $this->t('Revisions for %title', ['%title' => $node->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert $type revisions") || $account->hasPermission('revert all revisions') || $account->hasPermission('administer nodes')) && $node->access('update'));
    $delete_permission = (($account->hasPermission("delete $type revisions") || $account->hasPermission('delete all revisions') || $account->hasPermission('administer nodes')) && $node->access('delete'));

    $revision_log_counting = \Drupal::config('node_accessibility.settings')->get('revision_log_counting');
    if ($revision_log_counting == 1) {
      $severity_list = QuailApiSettings::get_severity_list();
    }

    $rows = [];
    $default_revision = $node->getRevisionId();

    foreach ($this->getRevisionIds($node, $node_storage) as $vid) {
      $validate_permission = \Drupal\node_accessibility\Access\ViewAccessCheck::check_node_access($account, $node->id(), $vid);

      /** @var \Drupal\node\NodeInterface $revision */
      $revision = $node_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->revision_timestamp->value, 'short');
        if ($vid != $node->getRevisionId()) {
          $link = $this->l($date, new Url('entity.node.revision', ['node' => $node->id(), 'node_revision' => $vid]));
        }
        else {
          $link = $node->link($date);
        }

        $validation_text = NULL;
        $validation_text_count = NULL;
        $validate_stats = ProblemsStorage::load_problem_stats((int) $node->id(), (int) $vid);

        if (!empty($validate_stats['uid'])) {
          $validate_account = User::load($validate_stats['uid']);
          $validate_stamp = $this->dateFormatter->format($validate_stats['timestamp'], 'short');
          if ($validate_account instanceof User) {
            $validation_text = t('Accessibility validation performed by @account on @timestamp. ', ['@account' => $validate_account->getDisplayName(), '@timestamp' => $validate_stamp]);

            if ($revision_log_counting == 1) {
              $validation_text_count = NULL;
              foreach ($severity_list as $severity => $severity_name) {
                if (is_null($validation_text_count)) {
                  $validation_text_count = '';
                }
                else {
                  $validation_text_count .= ', ';
                }

                $count = (int) ProblemsStorage::load_problem_severity_count($severity, (int) $node->id(), (int) $vid);
                $validation_text_count .= $count . ' ' . $severity_name;
              }

              $validation_text_count = t('@message have been detected.', ['@message' => $validation_text_count]);
            }
          }
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if accessibility_validation %}<div class="revision-log-accessibility_validation">{{ accessibility_validation }}</div>{% endif %}{% if accessibility_validation %}<div class="revision-log-accessibility_validation-count">{{ accessibility_validation_count }}</div>{% endif %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'accessibility_validation' => $validation_text,
              'accessibility_validation_count' => $validation_text_count,
              'message' => ['#markup' => $revision->revision_log->value, '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];

        // @todo Simplify once https://www.drupal.org/node/2334319 lands.
        $this->renderer->addCacheableDependency($column['data'], $username);
        $row[] = $column;

        if ($vid == $default_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];

          $rows[] = [
            'data' => $row,
            'class' => ['revision-current'],
          ];
        }
        else {
          $links = [];
          if ($validate_permission) {
            $links['accessibility_validate'] = [
              'title' => $vid < $node->getRevisionId() ? $this->t('Accessibility') : $this->t('Accessibility validate this revision.'),
              'url' => Url::fromRoute('node_accessibility.validate_revision', ['node' => $node->id(), 'node_revision' => $vid]),
            ];
          }

          if ($revert_permission) {
            $links['revert'] = [
              'title' => $vid < $node->getRevisionId() ? $this->t('Revert') : $this->t('Set as current revision'),
              'url' => $has_translations ?
                Url::fromRoute('node.revision_revert_translation_confirm', ['node' => $node->id(), 'node_revision' => $vid, 'langcode' => $langcode]) :
                Url::fromRoute('node.revision_revert_confirm', ['node' => $node->id(), 'node_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('node.revision_delete_confirm', ['node' => $node->id(), 'node_revision' => $vid]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];

          $rows[] = $row;
        }
      }
    }

    $build['node_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#attached' => [
        'library' => ['node/drupal.node.admin'],
      ],
      '#attributes' => ['class' => 'node-revision-table'],
    ];

    $build['pager'] = ['#type' => 'pager'];

    return $build;
  }
}
