<?php

namespace Drupal\quizard\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Quiz results entities.
 */
class QuizResultsViewsData extends EntityViewsData implements EntityViewsDataInterface {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['quiz_results']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Quiz results'),
      'help' => $this->t('The Quiz results ID.'),
    );

    return $data;
  }

}
