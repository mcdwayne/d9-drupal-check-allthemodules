<?php
/**
 * @file
 * Contains \Drupal\powertagging_corpus\Form\PowerTaggingCorpusOverviewForm.
 */

namespace Drupal\powertagging_corpus\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\powertagging\Entity\PowerTaggingConfig;
use Drupal\semantic_connector\SemanticConnector;

class PowerTaggingCorpusOverviewForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'powertagging_corpus_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['powertagging_corpus_title'] = array(
      '#type' => 'markup',
      '#markup' => '<h3 class="semantic-connector-table-title">' . t('PowerTagging Corpus Management - Corpora List') . '</h3>',
    );

    $connections = SemanticConnector::getConnectionsByType('pp_server');
    if (!empty($connections)) {
      $connection_options = array();
      /* @var $connection \Drupal\semantic_connector\Entity\SemanticConnectorConnection */
      foreach ($connections as $connection) {
        $connection_options[$connection->getId()] = $connection->getTitle();
      }
      $form['pp_connections'] = array(
        '#type' => 'select',
        '#title' => t('Select the PoolParty server'),
        '#options' => $connection_options,
        '#default_value' => key($connection_options),
        '#ajax' => array(
          'callback' => '::loadCorporaInfo',
          'wrapper' => 'powertagging-corpus-corpora-table',
          'method' => 'replace',
          'effect' => 'fade',
        ),
        '#attributes' => array(
          'autocomplete' => 'off',
        ),
      );

      /* @var $connection \Drupal\semantic_connector\Entity\SemanticConnectorConnection */
      foreach ($connections as $connection) {
        $projects = $connection->getApi('PPT')->getProjects();

        // Get the project options for the currently configured PoolParty server.
        $project_options = array();
        foreach ($projects as $project) {
          $project_options[$project['id']] = $project['title'];
        }

        // Configuration set admin page.
        $form['pp_projects_' . $connection->getId()] = array(
          '#type' => 'select',
          '#title' => 'Select a project',
          '#prefix' => '<div id="projects-replace">',
          '#suffix' => '</div>',
          '#options' => $project_options,
          '#default_value' => key($project_options),
          '#ajax' => array(
            'callback' => '::loadCorporaInfo',
            'wrapper' => 'powertagging-corpus-corpora-table',
            'method' => 'replace',
            'effect' => 'fade',
            'event' => 'change',
          ),
          '#states' => array(
            'visible' => array(
              ':input[name="pp_connections"]' => array('value' => $connection->getId()),
            ),
          ),
          '#attributes' => array(
            'autocomplete' => 'off',
          ),
        );

        // Set a default value.
        if (empty($form_state->getValue('pp_projects_' . $connection->getId()))) {
          $form_state->setValue('pp_projects_' . $connection->getId(), key($project_options));
        }

        $credentials = $connection->getCredentials();
        $connection_options[implode('|', array(
          $connection->getTitle(),
          $connection->getUrl(),
          $credentials['username'],
          $credentials['password'],
        ))] = $connection->getTitle();
      }

      // Set a default value.
      if (empty($form_state->getValue('pp_connections'))) {
        $form_state->setValue('pp_connections', key($connection_options));
      }
    }

    $analysis_running = FALSE;

    // Add a default corpus list table.
    $rows = array();
    $values = $form_state->getValues();
    if (isset($values['pp_connections']) && isset($values['pp_projects_' . $values['pp_connections']]) && !empty($values['pp_projects_' . $values['pp_connections']])) {
      $connection = SemanticConnector::getConnection('pp_server', $values['pp_connections']);
      $project_id = $values['pp_projects_' . $values['pp_connections']];

      /** @var \Drupal\semantic_connector\Api\SemanticConnectorPPTApi $ppt_api */
      $ppt_api = $connection->getApi('PPT');
      $corpora = $ppt_api->getCorpora($project_id);
      if (!empty($corpora)) {
        $pp_languages = $ppt_api->getLanguages();
        $powertagging_configs = PowerTaggingConfig::loadMultiple();
        $analysis_running = $ppt_api->isCorpusAnalysisRunning($project_id);
        foreach ($corpora as $corpus) {
          // List all PowerTagging configurations connected the corpora.
          $powertagging_links = array();
          /** @var PowerTaggingConfig $powertagging_config */
          foreach ($powertagging_configs as $powertagging_config) {
            // Correct connection and project.
            if ($powertagging_config->getConnectionId() == $connection->getId() && $powertagging_config->getProjectId() == $project_id) {
              $powertagging_config_settings = $powertagging_config->getConfig();
              $project_settings = $powertagging_config_settings['project'];
              // Correct corpus.
              if (isset($project_settings['corpus_id']) && $project_settings['corpus_id'] == $corpus['corpusId']) {
                $powertagging_links[] = Link::fromTextAndUrl($powertagging_config->getTitle(), Url::fromRoute('entity.powertagging.edit_config_form', array('powertagging' => $powertagging_config->id())))->toString();
              }
            }
          }

          // Build the metadata.
          $metadata = $ppt_api->getCorpusMetadata($project_id, $corpus['corpusId']);
          $metadata_array = array(
            t('Created') . ': ' . \Drupal::service('date.formatter')
              ->format(strtotime($metadata['created']), 'short'),
            t('Last modified') . ': ' . \Drupal::service('date.formatter')
              ->format(strtotime($metadata['lastModified']), 'short'),
            t('Language') . ': ' . $pp_languages[$metadata['language']],
            t('Stored documents') . ': ' . $metadata['storedDocuments'],
            t('Extracted terms') . ': ' . $metadata['extractedTerms'],
          );

          // Set the operations.
          $actions = array(
            Link::fromTextAndUrl(t('Add content'), Url::fromRoute('powertagging_corpus.add_content_to_corpus', array('connection' => $connection->id(), 'project_id' => $project_id, 'corpus_id' => $corpus['corpusId']), array('attributes' => array('class' => $metadata['quality'] == 'good' ? array('semantic-connector-italic') : array()))))->toString(),
            Link::fromTextAndUrl(t('Analyze corpus'), Url::fromRoute('powertagging_corpus.analyze_corpus', array('connection' => $connection->id(), 'project_id' => $project_id, 'corpus_id' => $corpus['corpusId']), array('attributes' => array('class' => ($analysis_running || $corpus['upToDate']) ? array('semantic-connector-italic') : array()))))->toString()
          );

          // Create the row.
          $rows[] = array(
            $corpus['corpusName'],
            new FormattableMarkup('<div class="semantic-connector-led led-' . ($metadata['quality'] == 'good' ? 'green' : ($metadata['quality'] == 'moderate' ? 'yellow' : 'red') . '" title="' . $metadata['quality']) . '"></div>' . (!empty($metadata['quality']) ? $metadata['quality'] : 'no analysis run yet'), array()),
            new FormattableMarkup('<ul><li>' . implode('</li><li>', $metadata_array) . '</li></ul>', array()),
            new FormattableMarkup($corpus['upToDate'] ? t('yes') : '<b>' . t('NO') . '</b>', array()),
            (!empty($powertagging_links) ? new FormattableMarkup('<ul><li>' . implode('</li><li>', $powertagging_links) . '</li></ul>', array()) : '-'),
            new FormattableMarkup(implode(' | ', $actions), array()),
          );
        }
      }
    }

    $form['powertagging_corpus_configurations'] = array(
      '#theme' => 'table',
      '#header' => array(
        t('Corpus name'),
        t('Quality'),
        t('Additional information'),
        t('Up to date'),
        t('Connected PowerTagging configurations'),
        t('Operations'),
      ),
      '#rows' => $rows,
      '#empty' => t('The selected project does not yet have any corpora.'),
      '#caption' => $analysis_running ? new FormattableMarkup('<div class="messages warning">' . t('A corpus analysis is currently running for one of the corpora of the selected project.') . '</div>', array()) : NULL,
      '#attributes' => array(
        'id' => 'powertagging-corpus-corpora-table',
        //'class' => array('semantic-connector-tablesorter'),
      ),
      /*'#attached' => array(
        'js' => array(drupal_get_path('module', 'powertagging_corpus') . '/js/powertagging_corpus.admin.js'),
      ),*/
    );

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
  }

  public function loadCorporaInfo(array &$form, FormStateInterface $form_state) {
    return $form['powertagging_corpus_configurations'];
  }
}