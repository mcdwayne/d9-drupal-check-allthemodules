<?php
/**
 * @file
 * Contains \Drupal\pp_graphsearch\Form\PPGraphSearchAgentDeleteForm.
 */

namespace Drupal\pp_graphsearch\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\pp_graphsearch\PPGraphSearch;
use Drupal\semantic_connector\SemanticConnector;

/**
 * The confirmation-form for deleting a PoolParty GraphSearch agent.
 */
class PPGraphSearchAgentDeleteForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pp_graphsearch_agent_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = (isset($_GET['agent_id_full']) && !empty($_GET['agent_id_full']) ? PPGraphSearch::loadAgent($_GET['agent_id_full']) : NULL);

    if (is_null($config)) {
      $form_state->setRedirectUrl(Url::fromRoute('pp_graphsearch.list_agents'));
    }
    else {
      $form_state->set('config', $config);
      $form['question'] = array(
        '#markup' => '<p>' . t('Are you sure you want to delete the agent "%source"?', array('%source' => $config['source'])) . '<br />' .
          t('This action cannot be undone.') . '</p>',
      );
      $form['deleteIndex'] = array(
        '#type' => 'checkbox',
        '#default_value' => true,
        '#title' => t('Delete all indexed feed items also.'),
      );
      $form['delete'] = array(
        '#type' => 'submit',
        '#value' => t('Delete agent'),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $form_state->get('config');
    $pp_graphsearch_api = SemanticConnector::getConnection('pp_server', $config['connection_id'])->getApi('sonr');
    $response = $pp_graphsearch_api->deleteAgent($config['id'], $config['search_space_id']);

    if ($form_state->getValue('deleteIndex')) {
      $pp_graphsearch_api->deleteIndex($config['source'], $config['search_space_id']);
    }

    if ($response) {
      \Drupal::messenger()->addMessage(t('%source has been deleted.', array('%source' => $config['source'])));
    }
    else {
      \Drupal::messenger()->addMessage(t('%source has not been deleted.', array('%source' => $config['source'])), 'error');
    }

    $form_state->setRedirectUrl(Url::fromRoute('pp_graphsearch.list_agents'));
  }
}
?>