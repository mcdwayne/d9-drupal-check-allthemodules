<?php
/**
 * @file
 * Contains \Drupal\pp_graphsearch\Form\PPGraphSearchSyncForm.
 */

namespace Drupal\pp_graphsearch\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
use Drupal\semantic_connector\SemanticConnector;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * A form to select the content types for updating or deleting of pings from
 * PoolParty GraphSearch server.
 */
class PPGraphSearchSyncForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pp_graphsearch_sync_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param int $connection_id
   *   A Semantic Connector connection ID.
   * @param string $search_space_id
   *   The ID of the GraphSearch Search Space to use.
   * @param string $operation
   *   The operation type to update or delete the pings.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $connection_id = '', $search_space_id = '', $operation = '') {
    // Check for the right operations.
    if (!in_array($operation, array('update', 'delete'))) {
      $form_state->setRedirectUrl(Url::fromRoute('pp_graphsearch.sync_overview'));
    }
    else {
      $variables = \Drupal::config('pp_graphsearch.settings')
        ->get('content_type_push');
      $content_types = array();
      if (!empty($variables)) {
        foreach ($variables as $content_type => $settings) {
          if (!empty($content_type) && $connection_id == $settings['connection_id'] && $search_space_id == $settings['search_space_id'] && $settings['active']) {
            if ($content_type == 'user') {
              $count = \Drupal::entityQuery('user')
                ->condition('uid', 0, '!=')
                ->count()
                ->execute();
              $option_title = t('!count Users', array(
                '!count' => $count,
              ));
            }
            else {
              $count = \Drupal::entityQuery('node')
                ->condition('type', $content_type)
                ->count()->execute();

              $node_type = $type = NodeType::load($content_type);
              $option_title = t('!count nodes from content type "@name"', array(
                '!count' => $count,
                '@name' => $node_type->label(),
              ));
            }
            $content_types[$content_type] = $option_title;
          }
        }
      }

      if (!empty($content_types)) {
        $form['content_types'] = array(
          '#type' => 'checkboxes',
          '#title' => ($operation == 'update' ? t('Select content types that will be added/updated on the PoolParty GraphSearch server') : t('Select content types that will be removed from the PoolParty GraphSearch server')),
          '#options' => $content_types,
          '#required' => TRUE,
        );
        $form['entities_per_request'] = array(
          '#type' => 'textfield',
          '#title' => t('Nodes per request'),
          '#description' => t('The number of nodes, that get processed during one HTTP request. (Allowed value range: 1 - 100)') . '<br />' . t('The higher this number is, the less HTTP requests have to be sent to the server until the batch finished tagging ALL your nodes, what results in a shorter duration of the bulk tagging process.') . '<br />' . t('Numbers too high can result in a timeout, which will break the whole bulk tagging process.'),
          '#required' => TRUE,
          '#default_value' => 10,
        );
        $form['operation'] = array(
          '#type' => 'value',
          '#value' => $operation,
        );
        $form['save'] = array(
          '#type' => 'submit',
          '#value' => ($operation == 'update' ? t('Synchronize') : t('Remove')),
        );
        $form['cancel'] = array(
          '#type' => 'link',
          '#title' => t('Cancel'),
          '#url' => Url::fromRoute('pp_graphsearch.sync_overview'),
        );
      }
      else {
        $form['error'] = array(
          '#type' => 'item',
          '#markup' => '<div class="messages error">' . t('No content type found for this PoolParty GraphSearch server.') . '</div>',
        );
        $pp_server = SemanticConnector::getConnection('pp_server', $connection_id);
        \Drupal::messenger()->addMessage(t('No connected content types found for the PoolParty GraphSearch server "%connection".', array('%connection' => $pp_server->getTitle())), 'error');
        $response = new RedirectResponse(Url::fromRoute('pp_graphsearch.sync_overview')->toString());
        return $response;
      }

      // Add CSS and JS.
      $form['#attached'] = array(
        'library' =>  array(
          'pp_graphsearch/admin_area',
        ),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entities_per_request = $form_state->getValue('entities_per_request');
    if (empty($entities_per_request) || !ctype_digit($entities_per_request) || (int) $entities_per_request == 0 || (int) $entities_per_request > 100) {
      $form_state->setErrorByName('entities_per_request', t('Only values in the range of 1 - 100 are allowed for field "Entities per request"'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $start_time = time();
    $nodes_per_request = $form_state->getValue('entities_per_request');
    $operation = $form_state->getValue('operation');

    // Configure the batch data.
    $batch = array(
      'title' => ($operation == 'update' ? t('Update entities') : t('Remove entities')),
      'operations' => array(),
      'init_message' => ($operation == 'update' ? t('Start with the updating of the entities.') : t('Start with the removing of the entities.')),
      'progress_message' => t('Process @current out of @total.'),
      'finished' => array('\Drupal\pp_graphsearch\PPGraphSearch','syncBatchFinished'),
    );

    // Set additional data.
    $nids = \Drupal::entityQuery('node')
      ->condition('type', $form_state->getValue('content_types'), 'IN')
      ->condition('status', \Drupal\node\NodeInterface::PUBLISHED)
      ->execute();
    $nid_count = count($nids);

    $uid_count = 0;
    if (in_array('user', $form_state->getValue('content_types'))) {
      $uids = \Drupal::entityQuery('user')
        ->condition('status', 1)
        ->execute();
      $uid_count = count($uids);
    }

    $info = array(
      'operation' => $operation,
      'total' => ($nid_count + $uid_count),
      'start_time' => $start_time,
    );

    // Set the synchronization operations for the nodes.
    for ($i = 0; $i < $nid_count; $i += $nodes_per_request) {
      $nodes = array_slice($nids, $i, $nodes_per_request);
      $batch['operations'][] = array(
        array('\Drupal\pp_graphsearch\PPGraphSearch','syncBatchProcess'),
        array($nodes, 'node', $info)
      );
    }

    // Set the synchronization operations for the users.
    for ($i = 0; $i < $uid_count; $i += $nodes_per_request) {
      $users = array_slice($uids, $i, $nodes_per_request);
      $batch['operations'][] = array(
        array('\Drupal\pp_graphsearch\PPGraphSearch','syncBatchProcess'),
        array($users, 'user', $info)
      );
    }

    // Start the batch
    batch_set($batch);

    $form_state->setRedirectUrl(Url::fromRoute('pp_graphsearch.sync_overview'));
  }
}
