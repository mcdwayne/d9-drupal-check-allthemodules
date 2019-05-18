<?php
/**
 * @file
 * Contains \Drupal\powertagging_corpus\Form\PowerTaggingCorpusAnalyzeCorpusForm.
 */

namespace Drupal\powertagging_corpus\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\semantic_connector\SemanticConnector;

class PowerTaggingCorpusAnalyzeCorpusForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'powertagging_corpus_analyze_corpus_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to start the corpus analysis?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromUserInput(\Drupal::request()->getRequestUri());
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Start the corpus analysis');
  }

  /**
   * {@inheritdoc}
   *
   * Start an analysis of an existing PoolParty corpus.
   *
   * @param \Drupal\semantic_connector\Entity\SemanticConnectorPPServerConnection $connection
   *   The PoolParty server connection to use.
   * @param string $project_id
   *   The ID of the PoolParty project to use.
   * @param string $corpus_id
   *   The corpus to start the analysis for.
   *
   * @return array
   *   The form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $connection = NULL, $project_id = '', $corpus_id = '') {
    $form = parent::buildForm($form, $form_state);

    if (!is_null($connection) && !empty($project_id) && !empty($corpus_id)) {
      $corpus_id = urldecode($corpus_id);

      /** @var \Drupal\semantic_connector\Api\SemanticConnectorPPTApi $ppt_api */
      $ppt_api = $connection->getApi('PPT');

      // Check if the project exists.
      $projects = $ppt_api->getProjects();
      foreach ($projects as $project) {
        if ($project['id'] == $project_id) {

          // Check if the corpus exists.
          $corpora = $ppt_api->getCorpora($project_id);
          foreach ($corpora as $corpus) {
            if ($corpus_id == $corpus['corpusId']) {
              $form['connection_id'] = array(
                '#type' => 'value',
                '#value' => $connection->id(),
              );
              $form['project_id'] = array(
                '#type' => 'value',
                '#value' => $project_id,
              );
              $form['corpus_id'] = array(
                '#type' => 'value',
                '#value' => $corpus_id,
              );
              $form['corpus_label'] = array(
                '#type' => 'value',
                '#value' => $corpus['corpusName'],
              );

              // Add basic information about the connection parameters.
              $content = '<p>';
              $content .= t('PoolParty connection') . ': <b>' . $connection->getTitle() . '</b><br />';
              $content .= t('Project') . ': <b>' . $project['title'] . '</b><br />';
              $content .= t('Corpus') . ': <b>' . $corpus['corpusName'] . '</b>';
              $content .= '</p>';

              $content .= '<p>' . t('This process gets the corpus up to date, improving the quality of the free term extraction.') . '</p>';

              $form['description'] = array(
                '#markup' => $content,
              );

              return $form;
            }
          }

          drupal_set_message(t('The selected corpus could not be found in the PoolParty project.'), 'error');
          break;
        }
      }
      drupal_set_message(t('The selected project could not be found on the PoolParty server.'), 'error');
    }
    else {
      drupal_set_message(t('The parameters provided are incorrect.'), 'error');
    }

    $form_state->setRedirectUrl(Url::fromRoute('powertagging_corpus.overview'));
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
    $values = $form_state->getValues();
    $connection = $connection = SemanticConnector::getConnection('pp_server', $values['connection_id']);

    /** @var \Drupal\semantic_connector\Api\SemanticConnectorPPTApi $ppt_api */
    $ppt_api = $connection->getApi('PPT');

    if (!$ppt_api->isCorpusAnalysisRunning($values['project_id'])) {
      $result = $ppt_api->analyzeCorpus($values['project_id'], $values['corpus_id']);
      if ($result['success']) {
        drupal_set_message(t('Successfully started an analysis for corpus "%corpusname".', array('%corpusname' => $values['corpus_label'])));
      }
      else {
        drupal_set_message(t('An error occurred while starting the analysis of corpus "%corpusname".', array('@corpusname' => $values['corpus_label']) . (isset($result['message']) && !empty($result['message'])) ? ' message: ' . $result['message'] : ''), 'error');
      }
    }
    else {
      drupal_set_message(t('There is already a corpus analysis running for the selected project. Only one corpus analysis for a PoolParty project can run at a time.'), 'error');
    }

    $form_state->setRedirectUrl(Url::fromRoute('powertagging_corpus.overview'));
  }
}