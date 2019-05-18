<?php

namespace Drupal\merci_line_item\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\merci_line_item\Entity\MerciLineItemInterface;

/**
 * Class MerciLineItemController.
 *
 *  Returns responses for Merci Line Item routes.
 *
 * @package Drupal\merci_line_item\Controller
 */
class MerciLineItemController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Merci Line Item  revision.
   *
   * @param int $merci_line_item_revision
   *   The Merci Line Item  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($merci_line_item_revision) {
    $merci_line_item = $this->entityManager()->getStorage('merci_line_item')->loadRevision($merci_line_item_revision);
    $view_builder = $this->entityManager()->getViewBuilder('merci_line_item');

    return $view_builder->view($merci_line_item);
  }

  /**
   * Page title callback for a Merci Line Item  revision.
   *
   * @param int $merci_line_item_revision
   *   The Merci Line Item  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($merci_line_item_revision) {
    $merci_line_item = $this->entityManager()->getStorage('merci_line_item')->loadRevision($merci_line_item_revision);
    return $this->t('Revision of %title from %date', array('%title' => $merci_line_item->label(), '%date' => format_date($merci_line_item->getRevisionCreationTime())));
  }

  /**
   * Generates an overview table of older revisions of a Merci Line Item .
   *
   * @param \Drupal\merci_line_item\Entity\MerciLineItemInterface $merci_line_item
   *   A Merci Line Item  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(MerciLineItemInterface $merci_line_item) {
    $account = $this->currentUser();
    $langcode = $merci_line_item->language()->getId();
    $langname = $merci_line_item->language()->getName();
    $languages = $merci_line_item->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $merci_line_item_storage = $this->entityManager()->getStorage('merci_line_item');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $merci_line_item->label()]) : $this->t('Revisions for %title', ['%title' => $merci_line_item->label()]);
    $header = array($this->t('Revision'), $this->t('Operations'));

    $revert_permission = (($account->hasPermission("revert all merci line item revisions") || $account->hasPermission('administer merci line item entities')));
    $delete_permission = (($account->hasPermission("delete all merci line item revisions") || $account->hasPermission('administer merci line item entities')));

    $rows = array();

    $vids = $merci_line_item_storage->revisionIds($merci_line_item);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\merci_line_item\MerciLineItemInterface $revision */
      $revision = $merci_line_item_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->revision_timestamp->value, 'short');
        if ($vid != $merci_line_item->getRevisionId()) {
          $link = $this->l($date, new Url('entity.merci_line_item.revision', ['merci_line_item' => $merci_line_item->id(), 'merci_line_item_revision' => $vid]));
        }
        else {
          $link = $merci_line_item->link($date);
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
              Url::fromRoute('entity.merci_line_item.translation_revert', ['merci_line_item' => $merci_line_item->id(), 'merci_line_item_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.merci_line_item.revision_revert', ['merci_line_item' => $merci_line_item->id(), 'merci_line_item_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.merci_line_item.revision_delete', ['merci_line_item' => $merci_line_item->id(), 'merci_line_item_revision' => $vid]),
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

    $build['merci_line_item_revisions_table'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    );

    return $build;
  }

}
