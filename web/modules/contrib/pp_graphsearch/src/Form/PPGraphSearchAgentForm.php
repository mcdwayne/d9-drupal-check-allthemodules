<?php
/**
 * @file
 * Contains \Drupal\pp_graphsearch\Form\PPGraphSearchAgentForm.
 */

namespace Drupal\pp_graphsearch\Form;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\pp_graphsearch\PPGraphSearch;
use Drupal\semantic_connector\Entity\SemanticConnectorPPServerConnection;
use Drupal\semantic_connector\SemanticConnector;

/**
 * The form to add or edit a PoolParty GraphSearch agents.
 */
class PPGraphSearchAgentForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pp_graphsearch_agent_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = (isset($_GET['agent_id_full']) && !empty($_GET['agent_id_full']) ? PPGraphSearch::loadAgent($_GET['agent_id_full']) : NULL);

    $form['source'] = array(
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#description' => t('Name of the agent.'),
      '#size' => 35,
      '#maxlength' => 60,
      '#default_value' => (!is_null($config)) ? $config['source'] : '',
      '#required' => TRUE,
    );

    $form['url'] = array(
      '#type' => 'textfield',
      '#title' => t('RSS URL'),
      '#description' => t('RSS URL of the source.'),
      '#size' => 35,
      '#maxlength' => 255,
      '#default_value' => (!is_null($config)) ? $config['url'] : '',
      '#required' => TRUE,
    );

    /* The space key is not important for now
    $form['spaceKey'] = array(
      '#type' => 'textfield',
      '#title' => t('Type'),
      '#description' => t('The type of the source (e.g. internal, external) shown in a separate facet.'),
      '#size' => 35,
      '#maxlength' => 60,
      '#default_value' => (!is_null($config)) ? $config['spaceKey'] : '',
    );
    */

    $form['periodMillis'] = array(
      '#type' => 'select',
      '#title' => t('Crawling period'),
      '#description' => t('The period when the agent is to be re-crawled.'),
      '#options' => array(
        '3600000' => t('hourly'),
        '86400000' => t('daily'),
        '604800000' => t('weekly'),
      ),
      '#default_value' => (!is_null($config)) ? $config['periodMillis'] : '86400000',
      '#required' => TRUE,
    );

    // Update existing config.
    if (!is_null($config)) {
      $form['connection_id'] = array(
        '#type' => 'hidden',
        '#value' => $config['connection_id'],
      );
      $form['search_space_id'] = array(
        '#type' => 'hidden',
        '#value' => $config['search_space_id'],
      );
      $form['agentid'] = array(
        '#type' => 'hidden',
        '#value' => $config['id'],
      );
    }
    // Create a new config --> choose sOnr-configuration.
    else {
      $graphsearch_options = array();
      $search_space_options = array();
      $connections = SemanticConnector::getConnectionsByType('pp_server');
      if (!empty($connections)) {
        foreach ($connections as $connection) {
          /** @var SemanticConnectorPPServerConnection $connection */
          $connection_config = $connection->getConfig();
          if (!empty($connection_config['graphsearch_configuration'])) {
            $graphsearch_options[$connection->id()] = $connection->getTitle();

            $graphsearch_config = $connection_config['graphsearch_configuration'];
            if (isset($graphsearch_config['version']) && version_compare($graphsearch_config['version'], '6.1', '>=')) {
              $search_space_options[$connection->getId()] = [];
              $search_spaces = SemanticConnector::getGraphSearchSearchSpaces($graphsearch_config);
              foreach ($search_spaces as $search_space) {
                $search_space_options[$connection->getId()][$search_space['id']] = $search_space['name'];
              }
            }
          }
        }
      }
      $form['connection_id'] = array(
        '#type' => 'select',
        '#title' => t('PoolParty GraphSearch server'),
        '#description' => t('Choose the PoolParty GraphSearch server this agent will be created for.'),
        '#options' => $graphsearch_options,
        '#required' => TRUE,
      );

      foreach ($search_space_options as $connection_id => $search_space_connection_options) {
        $form['search_space_id_' . $connection_id] = array(
          '#type' => 'select',
          '#title' => t('Search space'),
          '#description' => t('Select the search space this agent will be created for.'),
          '#options' => $search_space_connection_options,
          '#states' => array(
            'visible' => array(':input[name="connection_id"]' => array('value' => $connection_id)),
            'required' => array(':input[name="connection_id"]' => array('value' => $connection_id)),
          ),
        );
      }
    }

    $form['save'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );
    $form['cancel'] = array(
      '#type' => 'link',
      '#title' => t('Cancel'),
      '#url' => Url::fromRoute('pp_graphsearch.list_agents'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!UrlHelper::isValid($form_state->getValue('url'), TRUE)) {
      $form_state->setErrorByName('url', t('The field URL must be a valid URL.'));
    }

    if (!is_null($form_state->getValue('connection_id')) && isset($form['search_space_id_' . $form_state->getValue('connection_id')])) {
      $form_item_id = 'search_space_id_' . $form_state->getValue('connection_id');
      if (empty($form_state->getValue($form_item_id))) {
        $form_state->setErrorByName($form_item_id, t('A search space has to be selected for the selected GraphSearch server.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\semantic_connector\Api\SemanticConnectorSonrApi $pp_graphsearch_api */
    $pp_graphsearch_api = SemanticConnector::getConnection('pp_server', $form_state->getValue('connection_id'))->getApi('sonr');
    $config = array(
      'username' => \Drupal::currentUser()->getAccountName(),
      'source' => $form_state->getValue('source'),
      'url' => $form_state->getValue('url'),
      'periodMillis' => $form_state->getValue('periodMillis'),
    );

    if (!empty($form_state->getValue('agentid'))) {
      $response = $pp_graphsearch_api->updateAgent($form_state->getValue('agentid'), $config, $form_state->getValue('search_space_id'));
    }
    else {
      $search_space_id = '';
      if (!empty($form_state->getValue('search_space_id_' . $form_state->getValue('connection_id')))) {
        $search_space_id = $form_state->getValue('search_space_id_' . $form_state->getValue('connection_id'));
      }
      $response = $pp_graphsearch_api->addAgent($config, $search_space_id);
    }
    if ($response) {
      \Drupal::messenger()->addMessage(t('PoolParty GraphSearch agent %title has been saved.', array('%title' => $form_state->getValue('source'))));
    }
    else {
      \Drupal::messenger()->addMessage(t('PoolParty GraphSearch agent %title has not been saved.', array('%title' => $form_state->getValue('source'))), 'error');
    }

    $form_state->setRedirectUrl(Url::fromRoute('pp_graphsearch.list_agents'));
  }
}
?>