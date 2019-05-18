<?php

namespace Drupal\anonymous_publishing_cl\Form;

use Drupal\comment\Entity\Comment;
use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This class defines the moderation setting form for this module, available
 * at : admin/config/people/anonymous_publishing_cl/moderation
 */
class AnonymousPublishingClAdminModeration extends FormBase {

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'));
  }

  /**
   * Constructs a \Drupal\anonymous_publishing_cl\Form\AnonymousPublishingClAdminModeration object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection service.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'anonymous_publishing_cl_admin_moderation';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    // Build an 'Update options' form.
    $form['options'] = array(
      '#type' => 'details',
      '#title' => $this->t('Update options'),
      '#open' => TRUE,
      '#attributes' => array('class' => array('container-inline')),
    );

    $options = array(
      'publish' => $this->t('Publish the selected items'),
      'unpublish' => $this->t('Unpublish the selected items'),
    );
    $form['options']['operation'] = array(
      '#type' => 'select',
      '#title' => $this->t('Action'),
      '#title_display' => 'invisible',
      '#options' => $options,
      '#default_value' => 'publish',
    );
    $form['options']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Update'),
    );

    $form['apm_info'] = [
      '#markup' => t('<p>The following table shows all nodes that have been verified by e-mail. You may publish or unpublish by selecting the corresponding line(s) and perform the update action.</p>')
    ];

    $header = array(
      'title' => array(
        'data' => $this->t('Title'),
        'specifier' => 'title',
        'sort' => 'desc',
      ),
      'type' => array(
        'data' => $this->t('Type'),
        'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
      ),
      'email' => array(
        'data' => $this->t('E-mail'),
      ),
      'published' => array(
        'data' => $this->t('Published'),
        'sort' => 'desc',
        'specifier' => 'published',
      ),
    );

    $options = array();
    $hidden_values = array();

    // Fetch all nodes that has been verified.
    $results = $this->getAllContentsToModerate($header);

    foreach ($results as $row) {

      // Retrieve the title and status of the comment or node depending on
      // nature.
      if ($row->cid) {
        $comment = Comment::load($row->cid);
        if ($comment) {
          $title = $comment->getSubject();
          $url = $comment->permalink();
          $status = $comment->getStatus() ? $this->t('Published') : $this->t('Unpublished');
        }
        else {
          $title = $this->t('-deleted-');
          $url = null;
          $status = $this->t('Unpublished');
        }
        $type = 'comment';
        $id = $row->cid;
      }
      else {
        $node = Node::load($row->nid);
        if ($node) {
          $title = $node->getTitle();
          $url = Url::fromUri($node->url('canonical', array('absolute' => TRUE)));
          $status = $node->isPublished() ? $this->t('Published') : $this->t('Unpublished');
        }
        else {
          $title = $this->t('-deleted-');
          $url = null;
          $status = $this->t('Unpublished');
        }
        $type = 'node';
        $id = $row->nid;
      }

      if ($url) {
        $datatitle = array(
          '#type' => 'link',
          '#title' => Html::escape($title),
          '#url' => $url,
        );
      }
      else {
        $datatitle = array(
          '#markup' => Html::escape($title),
        );
      }

      $options[$id] = array(
        'title' => array(
          'data' => $datatitle,
        ),
        'type' => array(
          'data' => array(
            '#markup' => $this->t($type),
          ),
        ),
        'email' => array(
          'data' => array(
            '#markup' => $row->email
          ),
        ),
        'published' => array(
          'data' => array(
            '#markup' => $status
          ),
        ),
      );

      $hidden_values[$row->nid] = $type;
    }

    $form['hidden_values'] = array(
      '#type' => 'hidden',
      '#value' => serialize($hidden_values),
    );

    $form['items'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t('There is no verified content to moderate.'),
    );

    $form['pager'] = array('#type' => 'pager');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('items', array_diff($form_state->getValue('items'), array(0)));
    // We can't execute any 'Update options' if no items were selected.
    if (count($form_state->getValue('items')) == 0) {
      $form_state->setErrorByName('', $this->t('Select one or more items to perform the update on.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $operation = $form_state->getValue('operation');
    $ids = $form_state->getValue('items');
    $hidden = unserialize($form_state->getValue('hidden_values'));

    foreach ($ids as $id) {

      // Load the entity depending on type:
      $entity = NULL;
      switch ($hidden[$id]) {
        case 'node':
          $entity = Node::load($id);
          break;
        case 'comment':
          $entity = Comment::load($id);
          break;
      }

      if ($entity) {
        if ($operation == 'unpublish') {
          $entity->setPublished(FALSE);
          $entity->save();
        }
        elseif ($operation == 'publish') {
          $entity->setPublished(TRUE);
          $entity->save();
        }
      }
    }
    drupal_set_message($this->t('The update has been performed.'));
  }

  /**
   * Get all contents to moderate.
   *
   * @param int $test_id
   *   The test_id to retrieve results of.
   *
   * @return array
   *  Array of results grouped by test_class.
   */
  protected function getAllContentsToModerate($header) {
    $query = $this->database->select('anonymous_publishing', 'a');
    $query->fields('a');
    $query->where('a.verified > 0');
    $query->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->orderByHeader($header);
    $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(50);

    return $query->execute()->fetchAll();
  }
}