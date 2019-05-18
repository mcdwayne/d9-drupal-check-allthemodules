<?php

namespace Drupal\phones_contact\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\phones_contact\Entity\PhonesContactInterface;

/**
 * Class PhonesContactController.
 *
 *  Returns responses for Phones contact routes.
 */
class PhonesContactController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Phones contact  revision.
   *
   * @param int $phones_contact_revision
   *   The Phones contact  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($phones_contact_revision) {
    $phones_contact = $this->entityManager()->getStorage('phones_contact')->loadRevision($phones_contact_revision);
    $view_builder = $this->entityManager()->getViewBuilder('phones_contact');

    return $view_builder->view($phones_contact);
  }

  /**
   * Page title callback for a Phones contact  revision.
   *
   * @param int $phones_contact_revision
   *   The Phones contact  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($phones_contact_revision) {
    $phones_contact = $this->entityManager()->getStorage('phones_contact')->loadRevision($phones_contact_revision);
    return $this->t('Revision of %title from %date', ['%title' => $phones_contact->label(), '%date' => format_date($phones_contact->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Phones contact .
   *
   * @param \Drupal\phones_contact\Entity\PhonesContactInterface $phones_contact
   *   A Phones contact  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(PhonesContactInterface $phones_contact) {
    $account = $this->currentUser();
    $langcode = $phones_contact->language()->getId();
    $langname = $phones_contact->language()->getName();
    $languages = $phones_contact->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $phones_contact_storage = $this->entityManager()->getStorage('phones_contact');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $phones_contact->label()]) : $this->t('Revisions for %title', ['%title' => $phones_contact->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all phones contact revisions") || $account->hasPermission('administer phones contact entities')));
    $delete_permission = (($account->hasPermission("delete all phones contact revisions") || $account->hasPermission('administer phones contact entities')));

    $rows = [];

    $vids = $phones_contact_storage->revisionIds($phones_contact);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\phones_contact\PhonesContactInterface $revision */
      $revision = $phones_contact_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $phones_contact->getRevisionId()) {
          $link = $this->l($date, new Url('entity.phones_contact.revision', ['phones_contact' => $phones_contact->id(), 'phones_contact_revision' => $vid]));
        }
        else {
          $link = $phones_contact->link($date);
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
              'url' => Url::fromRoute('entity.phones_contact.revision_revert', ['phones_contact' => $phones_contact->id(), 'phones_contact_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.phones_contact.revision_delete', ['phones_contact' => $phones_contact->id(), 'phones_contact_revision' => $vid]),
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

    $build['phones_contact_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
