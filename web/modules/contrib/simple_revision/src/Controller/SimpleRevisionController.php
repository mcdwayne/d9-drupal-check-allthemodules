<?php

namespace Drupal\simple_revision\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\taxonomy\Entity\Term;

/**
 * SimpleRevisionController.
 */
class SimpleRevisionController extends ControllerBase {

  protected $database;

  /**
   * Constructor.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
            $container->get('database')
    );
  }

  /**
   * Getting all revisions.
   */
  public function getRevisions(RouteMatchInterface $route_match) {

    $taxonomy_term = $route_match->getParameter('taxonomy_term');

    $schema = $this->database->schema();

    if ($schema->tableExists('simple_revision')) {

      $result = $this->database->select('simple_revision', 'tt')
        ->fields('tt', ['trid', 'changed', 'revision_data', 'langcode'])
        ->condition('tt.entity_id', intval($taxonomy_term))
        ->execute()
        ->fetchAll();

      for ($i = 0; $i < count($result); $i++) {
        $trid[$i] = $result[$i]->trid;
        $changed[$i] = $result[$i]->changed;
        $changed_date[$i] = date('m/d/Y H:i:s', $changed[$i]);
        $data[$i] = [
          'trid' => $trid[$i],
          'changed' => $changed_date[$i],
          'entity_id' => $taxonomy_term,
        ];
      }

    };

    return [
      '#theme' => 'revision_data',
      '#revisiondata' => $data,
      '#attached' => [
        'library' => [
          'simple_revision/simple_revision',
        ],
      ],
    ];
  }

  /**
   * Reverting revision with id in url.
   */
  public function revert() {

    // Drupal get Request Query.
    $getRequest = \Drupal::request()->query;

    $id = $getRequest->get('id');

    $schema = $this->database->schema();

    if ($schema->tableExists('simple_revision')) {
      $result = $this->database->select('simple_revision', 'tt')
        ->fields('tt',
      ['trid', 'entity_id', 'revision_data', 'changed', 'langcode'])
        ->condition('tt.trid', intval($id))
        ->execute()
        ->fetchAll();

      $entity_id = $result[0]->entity_id;

      $term = Term::load($entity_id);

      $trdata = unserialize($result[0]->revision_data);

      foreach ($trdata as $key => $value) {
        $term->set($key, $value->getValue());
      }
      $term->save();
    }

    $url = '/taxonomy/term/' . $entity_id . '/edit';

    drupal_set_message($this->t('Reverted'));
    $response = new RedirectResponse($url);
    $response->send();

  }

  /**
   * Deleting revision with id in url.
   */
  public function delete() {

    // Drupal get Request Query.
    $getRequest = \Drupal::request()->query;

    $id = $getRequest->get('id');

    $schema = $this->database->schema();

    if ($schema->tableExists('simple_revision')) {
      $result = $this->database->select('simple_revision', 'tt')
        ->fields('tt',
      ['trid', 'entity_id', 'revision_data', 'changed', 'langcode'])
        ->condition('tt.trid', intval($id))
        ->execute()
        ->fetchAll();

      $entity_id = $result[0]->entity_id;

      $this->database->delete('simple_revision')
        ->condition('trid', intval($id))
        ->execute();
    }

    $url = '/taxonomy/term/' . $entity_id . '/revisions';

    drupal_set_message($this->t('Deleted'));
    $response = new RedirectResponse($url);
    $response->send();

  }

}
