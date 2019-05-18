<?php

namespace Drupal\people;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the people entity type.
 */
class PeopleViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['people']['table']['base']['access query tag'] = 'people_access';

    $data['people']['bulk_form'] = [
      'title' => $this->t('Operations bulk form'),
      'help' => $this->t('Add a form element that lets you run operations on multiple peoples.'),
      'field' => [
        'id' => 'bulk_form',
      ],
    ];

    $data['people']['users'] = [
      'title' => $this->t('Accounts'),
      'help' => $this->t("People's user accounts."),
      'field' => [
        'id' => 'people_users',
      ],
    ];

    return $data;
  }

}
