<?php

namespace Drupal\a12_best_bets\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\enterprise_search\Utility\A12Utils;

class A12BestBetsOverviewFormBase extends FormBase {
    private $rows;
    private $entity_type_id;
    private $entity_id;

    public function getFormId() {
        return 'a12_best_bets.entity.change.me';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $this->entity_type_id = explode("/", \Drupal::request()->getRequestUri())[1];
        if ($this->entity_type_id == 'taxonomy')
            $this->entity_type_id = 'taxonomy_term';

        $this->entity_id = \Drupal::request()->attributes->get($this->entity_type_id);

        $query = \Drupal::database()->select('a12_best_bets', 'a12_bb');
        $query->fields('a12_bb', ['content_id', 'query_text', 'exclude', 'environment', 'display_environment']);

        $query->condition('content_id', "%" . A12Utils::generateNodeSolrId($this->entity_type_id), "LIKE");

        $values = $query->execute()->fetchAll();
        $options = array();
        foreach ($values as $value) {
            $row = array();
            $row[] = $value->query_text;
            $row[] = $value->exclude === '0' ? 'false' : 'true';
            $row[] = $value->display_environment;
            $options[] = $row;
        }

        $form['bulk_update'] = array(
          '#type' => 'select',
          '#title' => $this->t('With selection'),
          '#options' => array(
            '1' => $this->t('Remove as best bet for search query'),
            '2' => $this->t('Exclude content from result set'),
            '3' => $this->t('Do not exclude content from result set')
          )
        );
        $form['submit'] = array(
          '#type' => 'submit',
          '#value' => $this->t('Apply')
        );
        $form['terms'] = array(
          '#type' => 'tableselect',
          '#header' => array(
            $this->t('SEARCH QUERY TEXT'),
            $this->t('EXCLUDED FROM RESULT SET'),
            $this->t('ENVIRONMENT'),
          ),
          '#options' => $options
        );

        $this->rows = $options;
        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $values = $form_state->getValues();

        $selected = $values['terms'];
        $bulk_option = $values['bulk_update'];

        $terms = array();
        foreach ($selected as $item) {
            if (is_string($item))
                $terms[] = $this->rows[intval($item)];
        }

        switch ($bulk_option) {
            case "1":
                foreach ($terms as $term) {
                    $query = \Drupal::database()->delete('a12 _best_bets');
                    $query->condition('content_id', '%entity:' . $this->entity_type_id . '/' . $this->entity_id . '%', 'LIKE')
                      ->condition('query_text', $term[0])
                      ->condition('display_environment', $term[2]);

                    $query->execute();
                }
                break;

            case "2":
                foreach ($terms as $term) {
                    $query = \Drupal::database()->update('a12 _best_bets')
                      ->fields(array('exclude' => 1));
                    $query->condition('content_id', '%entity:' . $this->entity_type_id . '/' . $this->entity_id . '%', 'LIKE')
                      ->condition('query_text', $term[0])
                      ->condition('display_environment', $term[2]);

                    $query->execute();
                }
                break;

            case "3":
                foreach ($terms as $term) {
                    $query = \Drupal::database()->update('a12 _best_bets')
                      ->fields(array('exclude' => 0));
                    $query->condition('content_id', '%entity:' . $this->entity_type_id . '/' . $this->entity_id . '%', 'LIKE')
                      ->condition('query_text', $term[0])
                      ->condition('display_environment', $term[2]);

                    $query->execute();
                }
                break;
        }
    }
}