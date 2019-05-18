<?php

/**
 * @file
 * Contains \Drupal\pp_graphsearch_similar\Form\PPGraphSearchSimilarConfigForm.
 */

namespace Drupal\pp_graphsearch_similar\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\pp_graphsearch_similar\Entity\PPGraphSearchSimilarConfig;
use Drupal\semantic_connector\Entity\SemanticConnectorPPServerConnection;
use Drupal\semantic_connector\SemanticConnector;

class PPGraphSearchSimilarConfigForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var PPGraphSearchSimilarConfig $entity */
    $entity = $this->entity;

    $configuration = $entity->getConfig();

    $connection_overrides = \Drupal::config('semantic_connector.settings')->get('override_connections');
    $overridden_values = array();
    if (isset($connection_overrides[$entity->id()])) {
      $overridden_values = $connection_overrides[$entity->id()];
    }

    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#description' => t('Name of the PoolParty GraphSearch SeeAlso widget.'). (isset($overridden_values['title']) ? ' <span class="semantic-connector-overridden-value">' . t('Warning: overridden by variable') . '</span>' : ''),
      '#size' => 35,
      '#maxlength' => 255,
      '#default_value' => $entity->getTitle(),
      '#required' => TRUE,
    );

    /** @var SemanticConnectorPPServerConnection $connection */
    $connection = $entity->getConnection();
    // Get the search space label.
    $search_space_id = $entity->getSearchSpaceId();
    $connection_config = $connection->getConfig();
    $graphsearch_config = $connection_config['graphsearch_configuration'];
    $search_space_label = t('<invalid search space selected>');
    if (is_array($graphsearch_config)) {
      if (is_array($graphsearch_config)) {
        if (version_compare($graphsearch_config['version'], '6.1', '>=')) {
          $search_spaces = SemanticConnector::getGraphSearchSearchSpaces($graphsearch_config);
          foreach ($search_spaces as $search_space) {
            if ($search_space['id'] == $search_space_id) {
              $search_space_label = $search_space['name'];
              break;
            }
          }
        }
        else {
          $projects = $connection->getApi('PPT')->getProjects();
          foreach ($projects as $project) {
            if (isset($graphsearch_config['projects'][$project['id']]) && $project['id'] == $search_space_id) {
              $search_space_label = $project['title'];
              break;
            }
          }
        }
      }
    }

    // Add information about the connection.
    $connection_markup = '';
    // Check the PoolParty server version if required.
    if (\Drupal::config('semantic_connector.settings')->get('version_checking')) {
      $version_messages = array();

      $ppx_api_version_info = $connection->getVersionInfo('PPX');
      if (version_compare($ppx_api_version_info['installed_version'], $ppx_api_version_info['latest_version'], '<')) {
        $version_messages[] = t('The connected PoolParty server is not up to date. You are currently running version %installedversion, upgrade to version %latestversion to enjoy the new features.', array('%installedversion' => $ppx_api_version_info['installed_version'], '%latestversion' => $ppx_api_version_info['latest_version']));
      }

      $sonr_api_version_info = $connection->getVersionInfo('sonr');
      if (version_compare($sonr_api_version_info['installed_version'], $sonr_api_version_info['latest_version'], '<')) {
        $version_messages[] = t('The connected PoolParty GraphSearch server is not up to date. You are currently running version %installedversion, upgrade to version %latestversion to enjoy the new features.', array('%installedversion' => $sonr_api_version_info['installed_version'], '%latestversion' => $sonr_api_version_info['latest_version']));
      }

      if (!empty($version_messages)) {
        $connection_markup .= '<div class="messages warning"><div class="message">' . implode('</div><div class="message">', $version_messages) . '</div></div>';
      }
    }
    $connection_markup .= '<p id="pp-graphsearch-similar-connection-info">' . t('Connected PoolParty server') . ': <b>' . $connection->getTitle() . ' (' . $connection->getUrl() . ')</b><br />'
      . t('Selected search space') . ': <b>' . $search_space_label . '</b><br />'
      . Link::fromTextAndUrl(t('Change the connected PoolParty server or search space'), Url::fromRoute('entity.pp_graphsearch_similar.edit_form', array('pp_graphsearch_similar' => $entity->id())))->toString() . '</p>';
    $form['pp_connection_markup'] = array(
      '#type' => 'markup',
      '#markup' => $connection_markup,
    );

    $form['settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Widget settings'),
    );

    $form['settings']['max_items'] = array(
      '#type' => 'textfield',
      '#title' => t('Number of items to display'),
      '#description' => t('The maximum number of similar documents you want to display.'),
      '#size' => 15,
      '#maxlength' => 5,
      '#default_value' => $configuration['max_items'],
      '#required' => TRUE,
      '#element_validate' => array('::element_validate_integer_positive'),
    );

    // Add CSS and JS.
    $form['#attached'] = array(
      'library' =>  array(
        'pp_graphsearch_similar/admin_area',
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var PPGraphSearchSimilarConfig $entity */
    $entity = $this->entity;

    // Update and save the entity.
    $entity->set('title', $form_state->getValue('title'));
    $entity->set('config', array('max_items' => $form_state->getValue('max_items')));

    \Drupal::messenger()->addMessage(t('PoolParty GraphSearch SeeAlso widget %title has been saved.', array('%title' => $form_state->getValue('title'))));
    $entity->save();

    $form_state->setRedirectUrl(Url::fromRoute('entity.pp_graphsearch_similar.collection'));
  }

  public function element_validate_integer_positive($element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if ($value !== '' && (!is_numeric($value) || intval($value) != $value || $value <= 0)) {
      $form_state->setErrorByName($element, t('%name must be a positive integer.', array('%name' => $element['#title'])));
    }
  }
}