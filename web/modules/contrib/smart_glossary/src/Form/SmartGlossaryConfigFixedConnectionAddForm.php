<?php
/**
 * @file
 * Contains \Drupal\pp_graphsearch\Form\SmartGlossaryConfigFixedConnectionAddForm.
 */

namespace Drupal\smart_glossary\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\semantic_connector\Entity\SemanticConnectorSparqlEndpointConnection;
use Drupal\semantic_connector\SemanticConnector;
use Drupal\smart_glossary\SmartGlossary;

/**
 * The confirmation-form for adding a Smart Glossary configuration for a
 * predefined PP connection.
 */
class SmartGlossaryConfigFixedConnectionAddForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'smart_glossary_fixed_connection_add_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param SemanticConnectorSparqlEndpointConnection $connection
   *   The server connection
   */
  public function buildForm(array $form, FormStateInterface $form_state, $connection = NULL) {
    if (is_null($connection)) {
      drupal_set_message(t('An incorrect Sparql endpoint connection ID was given.'));
      $form_state->setRedirectUrl(Url::fromRoute('semantic_connector.overview'));
    }
    else {
      $form_state->set('connection_id', $connection->id());

      $form['question'] = array(
        '#markup' => '<p>' . t('Are you sure you want to create the PoolParty GraphSearch configuration?') . '<br />' .
          t('This action cannot be undone.') . '</p>',
      );
      $form['connection_details'] = array(
        '#markup' => 'Selected PoolParty server: <b>' . $connection->getTitle() . '</b>',
      );

      $form['title'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Title of the new config'),
        '#maxlength' => 255,
        '#default_value' => 'Smart Glossary configuration for ' . $connection->getTitle(),
        '#required' => TRUE,
      );

      $form['create'] = array(
        '#type' => 'submit',
        '#value' => t('Create configuration'),
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
    $connection = SemanticConnector::getConnection('sparql_endpoint', $form_state->get('connection_id'));
    $configuration_title = 'SmartGlossary configuration for ' . $connection->getTitle();

    // Set all the required variables and save the configuration.
    $new_smart_glossary_config = SmartGlossary::createConfiguration(
      $form_state->getValue('title'),
      '<none>',
      $connection->id()
    );

    drupal_set_message(t('Smart Glossary configuration "%title" has been created.', array('%title' => $configuration_title)));
    // Drupal Goto to forward a destination if one is available.
    if (isset($_GET['destination'])) {
      $destination = $_GET['destination'];
      unset($_GET['destination']);
      $form_state->setRedirectUrl(Url::fromRoute('entity.smart_glossary.edit_form', array('smart_glossary' => $new_smart_glossary_config->id()), array('query' => array('destination' => $destination))));
    }
    else {
      $form_state->setRedirectUrl(Url::fromRoute('entity.smart_glossary.edit_form', array('smart_glossary' => $new_smart_glossary_config->id())));
    }
  }
}
?>