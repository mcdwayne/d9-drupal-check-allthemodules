<?php
/**
 * @file
 * Contains \Drupal\pp_graphsearch_similar\Form\PPGraphSearchSimilarConfigFixedConnectionAddForm.
 */

namespace Drupal\pp_graphsearch_similar\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\pp_graphsearch_similar\PPGraphSearchSimilar;
use Drupal\semantic_connector\Entity\SemanticConnectorPPServerConnection;
use Drupal\semantic_connector\SemanticConnector;

/**
 * The confirmation-form for adding a PP GraphSearch configuration for a
 * predefined PP connection + project.
 */
class PPGraphSearchSimilarConfigFixedConnectionAddForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pp_graphsearch_similar_fixed_connection_add_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param SemanticConnectorPPServerConnection $connection
   *   The server connection
   * @param string $search_space_id
   *   The ID of the GraphSearch search space to use.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $connection = NULL, $project_id = '') {
    if (is_null($connection) || empty($project_id)) {
      \Drupal::messenger()->addMessage(t('An incorrect PoolParty connection ID or project ID was given.'), 'error');
      $form_state->setRedirectUrl(Url::fromRoute('semantic_connector.overview'));
    }
    else {
      $pp_config = $connection->getConfig();
      if (!empty($pp_config)) {
        foreach ($pp_config['projects'] as $project) {
          if ($project['id'] == $project_id) {
            $form_state->set('connection_id', $connection->id());
            $form_state->set('project_id', $project_id);

            $form['description'] = array(
              '#markup' => t('Selected PoolParty server:') . ' <b>' . $connection->getTitle() . '</b><br />' . t('Selected project:') . ' <b>' . $project['title'] . '</b>',
            );

            if (!empty($pp_config['graphsearch_configuration']) && isset($pp_config['graphsearch_configuration']['projects'][$project['id']])) {
              if (version_compare($pp_config['graphsearch_configuration']['version'], '6.1', '>=')) {
                $search_spaces = array();
                foreach ($pp_config['graphsearch_configuration']['projects'][$project['id']]['search_spaces'] as $search_space) {
                  $search_spaces[$search_space['id']] = $search_space['name'];
                }
                $form['search_space_id'] = array(
                  '#type' => 'select',
                  '#title' => t('Select search space'),
                  '#options' => $search_spaces,
                );
              }
              else {
                $form['search_space_id'] = array(
                  '#type' => 'value',
                  '#value' => $project_id,
                );
              }
            }

            $form['title'] = array(
              '#type' => 'textfield',
              '#title' => $this->t('Title of the new config'),
              '#maxlength' => 255,
              '#default_value' => 'PP GraphSearch SeeAlso widget for ' . $connection->getTitle() . ' (' . $project['title'] . ')',
              '#required' => TRUE,
            );

            // Save and cancel buttons.
            $form['save'] = array(
              '#type' => 'submit',
              '#value' => t('Create configuration'),
              '#prefix' => '<div class="form-actions form-wrapper">',
            );

            return $form;
          }
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('search_space_id'))) {
      $form_state->setErrorByName('search_space_id', t('Please select a search space.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $connection = SemanticConnector::getConnection('pp_server', $form_state->get('connection_id'));
    $pp_config = $connection->getConfig();
    $project_id = $form_state->get('project_id');
    $search_space_id = $form_state->getValue('search_space_id');

    // The project has a configured PoolParty GraphSearch server.
    if (isset($pp_config['graphsearch_configuration']) && !empty($pp_config['graphsearch_configuration']) && isset($pp_config['graphsearch_configuration']['projects'][$project_id]) && isset($pp_config['graphsearch_configuration']['projects'][$project_id]['search_spaces']) && isset($pp_config['graphsearch_configuration']['projects'][$project_id]['search_spaces'][$search_space_id])) {
      foreach ($pp_config['projects'] as $project) {
        if ($project['id'] == $project_id) {
          // Set all the required variables and save the configuration.
          $new_graphsearch_similar_config = PPGraphSearchSimilar::createConfiguration(
            $form_state->getValue('title'),
            $search_space_id,
            $connection->id()
          );

          \Drupal::messenger()->addMessage(t('PoolParty GraphSearch SeeAlso widget "%title" has been created.', array('%title' => $new_graphsearch_similar_config->getTitle())));
          // Drupal Goto to forward a destination if one is available.
          if (isset($_GET['destination'])) {
            $destination = $_GET['destination'];
            unset($_GET['destination']);
            $form_state->setRedirectUrl(Url::fromRoute('entity.pp_graphsearch_similar.edit_config_form', array('pp_graphsearch_similar' => $new_graphsearch_similar_config->id()), array('query' => array('destination' => $destination))));
          }
          else {
            $form_state->setRedirectUrl(Url::fromRoute('entity.pp_graphsearch_similar.edit_config_form', array('pp_graphsearch_similar' => $new_graphsearch_similar_config->id())));
          }
          break;
        }
      }
    }
    // No PoolParty GraphSearch server available for the selected project.
    else {
      \Drupal::messenger()->addMessage(t('There is no PoolParty GraphSearch configuration available for SearchSpace-ID "%searchspaceid" on PoolParty server "%ppservertitle"', array('%searchspaceid' => $search_space_id,'%ppservertitle' => $connection->getTitle())), 'error');
      $form_state->setRedirectUrl(Url::fromRoute('entity.pp_graphsearch_similar.collection'));
    }
  }
}
?>