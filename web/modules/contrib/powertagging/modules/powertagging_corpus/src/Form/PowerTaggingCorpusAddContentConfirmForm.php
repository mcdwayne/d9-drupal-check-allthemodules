<?php
/**
 * @file
 * Contains \Drupal\powertagging_corpus\Form\PowerTaggingCorpusAddContentConfirmForm.
 */
namespace Drupal\powertagging_corpus\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete PowerTagging entities.
 */
class PowerTaggingCorpusAddContentConfirmForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "powertagging_corpus_add_content_confirm_form";
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Confirm your selection');
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
    return t('Confirm');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, FormStateInterface $original_form_state = null, $corpus_info = null) {
    $form = parent::buildForm($form, $form_state);

    // Add basic information about the connection parameters.
    $content = '<p>';
    $content .= t('PoolParty connection') .  ': <b>' . $corpus_info['connection']->getTitle() . '</b><br />';
    $content .= t('Project') .  ': <b>' . $corpus_info['project']['title'] . '</b><br />';
    $content .= t('Corpus') .  ': <b>' . $corpus_info['corpus']['corpusName'] . '</b>';
    $content .= '</p>';

    // Add information about the content to push into the corpus.
    $content .= '<p>Content that gets pushed into the corpus:</p>';
    $start_date = 0;
    $end_date = 0;
    $values = $original_form_state->getValues();
    if ($values['use_date']) {
      $start_date = strtotime($values['date_from']['year'] . '-' . $values['date_from']['month'] . '-' . $values['date_from']['day']);
      $end_date = strtotime($values['date_to']['year'] . '-' . $values['date_to']['month'] . '-' . $values['date_to']['day']);
    }

    // Calculate the entity counts.
    $content_list = array();
    foreach ($values['content_selected'] as $entity_type => $content_types_fields) {
      foreach (array_keys($content_types_fields) as $content_type) {
        switch ($entity_type){
          case "node":
            $query = \Drupal::entityQuery('node');
            $query->condition('type', $content_type);
            break;

          case "taxonomy_term":
            $query = \Drupal::entityQuery('taxonomy_term');
            $query->condition('vid', $content_type);
            break;

          // Users.
          default:
            $query = \Drupal::entityQuery('user');
        }

        if ($entity_type != 'taxonomy_term' && $values['use_date']) {
          $query->condition('created', $start_date, '>=');
          $query->condition('created', $end_date, '<=');
        }

        $entity_count = $query->count()->execute();
        $content_list[] = $entity_count . ' ' . str_replace(' ', ' ', $entity_type) . 's' . ($entity_type != 'taxonomy_term' ? ' of type "' . $content_type . '"' : '');
      }
    }

    // Show a list of entity counts.
    $content .= '<ul><li>' . implode('</li><li>', $content_list) . '</li></ul>';
    //$form_state->set('selected_content', $content);

    $form['description'] = array(
      '#markup' => $content,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Only the submit handler of PowerTaggingCorpusAddContentForm gets called.
  }
}
