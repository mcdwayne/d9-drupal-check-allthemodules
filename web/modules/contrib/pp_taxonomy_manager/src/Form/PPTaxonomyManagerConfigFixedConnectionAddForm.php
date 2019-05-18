<?php
/**
 * @file
 * Contains \Drupal\pp_taxonomy_manager\Form\PPTaxonomyManagerConfigFixedConnectionAddForm.
 */

namespace Drupal\pp_taxonomy_manager\Form;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\pp_taxonomy_manager\Entity\PPTaxonomyManagerConfig;
use Drupal\pp_taxonomy_manager\PPTaxonomyManager;
use Drupal\semantic_connector\Entity\SemanticConnectorPPServerConnection;
use Drupal\semantic_connector\SemanticConnector;

/**
 * The confirmation-form for adding a PoolParty Taxonomy Manager configuration for a
 * predefined PP connection + project.
 */
class PPTaxonomyManagerConfigFixedConnectionAddForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pp_taxonomy_manager_fixed_connection_add_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param SemanticConnectorPPServerConnection $connection
   *   The server connection
   * @param string $project_id
   *   The ID of the project on the PoolParty server
   */
  public function buildForm(array $form, FormStateInterface $form_state, $connection = NULL, $project_id = '') {
    if (is_null($connection) || empty($project_id)) {
      drupal_set_message(t('An incorrect PoolParty connection ID or project ID was given.'), 'error');
      $form_state->setRedirectUrl(Url::fromRoute('semantic_connector.overview'));
    }
    else {
      $pp_config = $connection->getConfig();
      $project_title = '';
      foreach ($pp_config['projects'] as $config_project) {
        if ($config_project['id'] == $project_id) {
          $project_title = $config_project['title'];
          break;
        }
      }

      if (empty($project_title)) {
        drupal_set_message(t('The given project ID could not be found on the PoolParty server.'), 'error');
        $form_state->setRedirectUrl(Url::fromRoute('semantic_connector.overview'));
      }
      else {
        $form_state->set('connection_id', $connection->id());
        $form_state->set('project_id', $project_id);

        $form['description'] = array(
          '#markup' => t('Selected PoolParty server: %server', array(
            '%server' => new FormattableMarkup('<b>' . $connection->getTitle() . '</b>', array()),
          )),
        );

        $form['title'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Title of the new config'),
          '#maxlength' => 255,
          '#default_value' => 'PoolParty Taxonomy Manager config for ' . $connection->getTitle() . ' (' . $project_title . ')',
          '#required' => TRUE,
        );

        // Container: Level settings.
        $form['level_settings'] = array(
          '#type' => 'fieldset',
          '#title' => t('Select the taxonomy root level'),
        );
        $form['level_settings']['root_level'] = array(
          '#type' => 'radios',
          '#options' => array(
            'project' => t('Every taxonomy becomes a %rootlevel.', array('%rootlevel' => new FormattableMarkup('<b>' . t('PoolParty Project') . '</b>', array()))),
            'conceptscheme' => t('Every taxonomy becomes a %rootlevel in project "%projectname".', array('%rootlevel' => new FormattableMarkup('<b>' . t('Concept Scheme') . '</b>', array()), '%projectname' => $project_title)),
          ),
          '#default_value' => 'conceptscheme',
        );

        $form['save'] = array(
          '#type' => 'submit',
          '#value' => t('Create configuration'),
          '#prefix' => '<div class="admin-form-submit-buttons" style="margin-top: 40px;">',
        );
        $form['cancel'] = array(
          '#type' => 'link',
          '#title' => t('Cancel'),
          '#url' => Url::fromRoute('entity.pp_taxonomy_manager.collection'),
          '#suffix' => '</div>',
        );
      }
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
    $connection = SemanticConnector::getConnection('pp_server', $form_state->get('connection_id'));
    $project_id = $form_state->get('project_id');

    $pp_config = $connection->getConfig();
    foreach ($pp_config['projects'] as $project) {
      if ($project['id'] == $project_id) {
        $root_level = $form_state->getValue('root_level');
        $settings = PPTaxonomyManagerConfig::getDefaultConfig();
        $settings['root_level'] = $root_level;

        // Set all the required variables and save the configuration.
        $new_graphsearch_config = PPTaxonomyManager::createConfiguration(
          $form_state->getValue('title'),
          ($root_level == 'conceptscheme') ? $project_id : NULL,
          $connection->id(),
          $settings
        );

        drupal_set_message(t('PoolParty Taxonomy Manager configuration "%title" has been created.', array('%title' => $new_graphsearch_config->getTitle())));
        // Drupal Goto to forward a destination if one is available.
        if (isset($_GET['destination'])) {
          $destination = $_GET['destination'];
          unset($_GET['destination']);
          $form_state->setRedirectUrl(Url::fromRoute('entity.pp_taxonomy_manager.edit_config_form', array('pp_taxonomy_manager' => $new_graphsearch_config->id()), array('query' => array('destination' => $destination))));
        }
        else {
          $form_state->setRedirectUrl(Url::fromRoute('entity.pp_taxonomy_manager.edit_config_form', array('pp_taxonomy_manager' => $new_graphsearch_config->id())));
        }
        break;
      }
    }
  }
}
?>