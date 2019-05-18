<?php

namespace Drupal\box;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the box entity type.
 */
class BoxViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['box_field_data']['table']['base']['weight'] = -10;
    $data['box_field_data']['table']['wizard_id'] = 'box';

    $data['box_field_data']['id']['argument'] = [
      'id' => 'box_id',
      'name field' => 'title',
      'numeric' => TRUE,
      'validate type' => 'id',
    ];

    $data['box_field_data']['title']['field']['default_formatter_settings'] = ['link_to_entity' => TRUE];

    $data['box_field_data']['title']['field']['link_to_box default'] = TRUE;

    $data['box_field_data']['type']['argument']['id'] = 'box_type';

    $data['box_field_data']['langcode']['help'] = $this->t('The language of the box or translation.');

    $data['box_field_data']['status']['filter']['label'] = $this->t('Published status');
    $data['box_field_data']['status']['filter']['type'] = 'yes-no';
    // Use status = 1 instead of status <> 0 in WHERE statement.
    $data['box_field_data']['status']['filter']['use_equal'] = TRUE;

    $data['box_field_data']['status_extra'] = [
      'title' => $this->t('Published status or admin user'),
      'help' => $this->t('Filters out unpublished boxes if the current user cannot view it.'),
      'filter' => [
        'field' => 'status',
        'id' => 'box_status',
        'label' => $this->t('Published status or admin user'),
      ],
    ];

    $data['box']['box_bulk_form'] = [
      'title' => $this->t('Box operations bulk form'),
      'help' => $this->t('Add a form element that lets you run operations on multiple boxes.'),
      'field' => [
        'id' => 'box_bulk_form',
      ],
    ];

    // Bogus fields for aliasing purposes.
    // @todo Add similar support to any date field
    // @see https://www.drupal.org/node/2337507
    $data['box_field_data']['created_fulldate'] = [
      'title' => $this->t('Created date'),
      'help' => $this->t('Date in the form of CCYYMMDD.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_fulldate',
      ],
    ];

    $data['box_field_data']['changed_fulldate'] = [
      'title' => $this->t('Updated date'),
      'help' => $this->t('Date in the form of CCYYMMDD.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_fulldate',
      ],
    ];

    $data['box_field_data']['user_id']['help'] = $this->t('The user authoring the box. If you need more fields than the uid add the box: author relationship');
    $data['box_field_data']['user_id']['filter']['id'] = 'user_name';
    $data['box_field_data']['user_id']['relationship']['title'] = $this->t('Box author');
    $data['box_field_data']['user_id']['relationship']['help'] = $this->t('Relate box to the user who created it.');
    $data['box_field_data']['user_id']['relationship']['label'] = $this->t('author');

    $data['box_field_data']['uid_revision']['title'] = $this->t('User has a revision');
    $data['box_field_data']['uid_revision']['help'] = $this->t('All boxes where a certain user has a revision');
    $data['box_field_data']['uid_revision']['real field'] = 'nid';
    $data['box_field_data']['uid_revision']['filter']['id'] = 'box_uid_revision';
    $data['box_field_data']['uid_revision']['argument']['id'] = 'box_uid_revision';

    $data['box_field_revision']['table']['wizard_id'] = 'box_revision';

    // Advertise this table as a possible base table.
    $data['box_field_revision']['table']['base']['help'] = $this->t('Box revision is a history of changes on box.');
    $data['box_field_revision']['table']['base']['defaults']['title'] = 'title';

    $data['box_field_revision']['id']['argument'] = [
      'id' => 'box_id',
      'numeric' => TRUE,
    ];

    $data['box_field_revision']['id']['relationship']['id'] = 'standard';
    $data['box_field_revision']['id']['relationship']['base'] = 'box_field_data';
    $data['box_field_revision']['id']['relationship']['base field'] = 'id';
    $data['box_field_revision']['id']['relationship']['title'] = $this->t('Box');
    $data['box_field_revision']['id']['relationship']['label'] = $this->t('Get the actual box from a box revision.');

    $data['box_field_revision']['vid'] = [
      'argument' => [
        'id' => 'box_vid',
        'numeric' => TRUE,
      ],
      'relationship' => [
        'id' => 'standard',
        'base' => 'box_field_data',
        'base field' => 'vid',
        'title' => $this->t('Box'),
        'label' => $this->t('Get the actual box from a box revision.'),
      ],
    ] + $data['box_field_revision']['vid'];

    $data['box_field_revision']['langcode']['help'] = $this->t('The language the original box is in.');

    $data['box_revision']['revision_user']['help'] = $this->t('Relate a box revision to the user who created the revision.');
    $data['box_revision']['revision_user']['relationship']['label'] = $this->t('revision user');

    $data['box_field_revision']['table']['wizard_id'] = 'box_field_revision';

    $data['box_field_revision']['table']['join']['box_field_data']['left_field'] = 'vid';
    $data['box_field_revision']['table']['join']['box_field_data']['field'] = 'vid';

    $data['box_field_revision']['status']['filter']['label'] = $this->t('Published');
    $data['box_field_revision']['status']['filter']['type'] = 'yes-no';
    $data['box_field_revision']['status']['filter']['use_equal'] = TRUE;

    $data['box_field_revision']['langcode']['help'] = $this->t('The language of the box or translation.');

    $data['box_field_revision']['link_to_revision'] = [
      'field' => [
        'title' => $this->t('Link to revision'),
        'help' => $this->t('Provide a simple link to the revision.'),
        'id' => 'box_revision_link',
        'click sortable' => FALSE,
      ],
    ];

    $data['box_field_revision']['revert_revision'] = [
      'field' => [
        'title' => $this->t('Link to revert revision'),
        'help' => $this->t('Provide a simple link to revert to the revision.'),
        'id' => 'box_revision_link_revert',
        'click sortable' => FALSE,
      ],
    ];

    $data['box_field_revision']['delete_revision'] = [
      'field' => [
        'title' => $this->t('Link to delete revision'),
        'help' => $this->t('Provide a simple link to delete the content revision.'),
        'id' => 'box_revision_link_delete',
        'click sortable' => FALSE,
      ],
    ];

    return $data;
  }

}
