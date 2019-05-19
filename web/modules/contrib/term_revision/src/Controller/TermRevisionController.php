<?php

namespace Drupal\term_revision\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * TermRevisionController.
 */
class TermRevisionController extends ControllerBase {

  protected $database;
  protected $userStorage;

  /**
   * Constructor.
   */
  public function __construct(Connection $database, UserStorageInterface $user_storage) {
    $this->database = $database;
    $this->userStorage = $user_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
            $container->get('database'), $container->get('entity_type.manager')->getStorage('user')
    );
  }

  /**
   * Getting all revisions.
   */
  public function getRevisions(RouteMatchInterface $route_match) {
    $taxonomy_term = $route_match->getParameter('taxonomy_term');

    $schema = $this->database->schema();
    if ($schema->tableExists('term_revision')) {
      // Query for fetching id, changed time and user of all the revisions for
      // the given term.
      $result = $this->database->select('term_revision', 'tr')
        ->fields('tr', ['trid', 'changed', 'revision_user'])
        ->condition('tr.entity_id', intval($taxonomy_term))
        ->orderBy('changed', 'DESC')
        ->execute()
        ->fetchAll();

      // Header of the Revision table.
      $header = [$this->t('CHANGED'), $this->t('USER'), $this->t('OPERATIONS')];

      // Data to be rendered on revisions page of a term.
      $data = [];
      if (!empty($result)) {
        $revisionUser = $this->t('Anonymous');
        if ($result[0]->revision_user != -1) {
          $user = $this->userStorage->load($result[0]->revision_user);
          $userName = $user->getUsername();
          $revisionUser = Link::fromTextAndUrl($userName, Url::fromUri('internal:/user/' . $result[0]->revision_user));
        }
        // First row of Revision table marks the current revision.
        $data[0] = [
          Link::fromTextAndUrl(date('m/d/Y H:i:s', $result[0]->changed), Url::fromUri('internal:/taxonomy/term/' . $taxonomy_term)),
          $revisionUser,
          $this->t('Current Revision'),
        ];

        $rowcount = count($result);
        for ($i = 1; $i < $rowcount; $i++) {
          $revisionUser = $this->t('Anonymous');
          if ($result[$i]->revision_user != -1) {
            $user = $this->userStorage->load($result[$i]->revision_user);
            $userName = $user->getUsername();
            $revisionUser = Link::fromTextAndUrl($userName, Url::fromUri('internal:/user/' . $result[$i]->revision_user));
          }
          $revertLink = Link::fromTextAndUrl('Revert', Url::fromRoute('term_revision.revert', ['taxonomy_term' => $taxonomy_term, 'id' => $result[$i]->trid]));
          $deleteLink = Link::fromTextAndUrl('Delete', Url::fromRoute('term_revision.delete', ['taxonomy_term' => $taxonomy_term, 'id' => $result[$i]->trid]));
          $data[$i] = [
            Link::fromTextAndUrl(date('m/d/Y H:i:s', $result[$i]->changed),
                      Url::fromRoute('term_revision.view', ['taxonomy_term' => $taxonomy_term, 'revision_id' => $result[$i]->trid])),
            $revisionUser,
            $this->t('@revert | @delete', ['@revert' => $revertLink->toString(), '@delete' => $deleteLink->toString()]),
          ];
        }
      }
      return ['#type' => 'table', '#header' => $header, '#rows' => $data];
    }
  }

  /**
   * Getting revision data.
   */
  public function viewRevision(RouteMatchInterface $route_match) {
    // Term Id.
    $taxonomy_term = $route_match->getParameter('taxonomy_term');
    $revision_id = $route_match->getParameter('revision_id');

    // Database Schema.
    $schema = $this->database->schema();
    if ($schema->tableExists('term_revision')) {
      // Query for fetching the revision data for given revision id and term id.
      $result = $this->database->select('term_revision', 'tr')
        ->fields('tr', ['revision_data', 'changed', 'revision_user'])
        ->condition('tr.entity_id', intval($taxonomy_term))
        ->condition('tr.trid', intval($revision_id))
        ->execute()
        ->fetchAll();
      if (!empty($result)) {
        $serialized_data = $result[0]->revision_data;
        $trdata = unserialize($serialized_data);
        $notRequired = [
          'tid',
          'uuid',
          'langcode',
          'vid',
          'parent',
          'changed',
          'default_langcode',
        ];

        // Fiels's data of a Taxonomy Term.
        $data = [];
        foreach ($trdata as $key => $value) {
          if (!in_array($key, $notRequired)) {
            if (!empty($value->getValue())) {
              if (is_string($value->getFieldDefinition()->getLabel())) {
                $data[$value->getFieldDefinition()->getLabel()] = $value->getValue();
              }
              else {
                $data[$key] = $value->getValue();
              }
            }
          }
        }
        return [
          '#theme' => 'view_revision',
          '#revision_data' => $data,
        ];
      }
    }
    throw new NotFoundHttpException();
  }

}
