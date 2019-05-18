<?php

namespace Drupal\entity_gallery;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the entity gallery entity type.
 */
class EntityGalleryViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['entity_gallery_field_data']['table']['base']['weight'] = -10;
    $data['entity_gallery_field_data']['table']['base']['access query tag'] = 'entity_gallery_access';
    $data['entity_gallery_field_data']['table']['wizard_id'] = 'entity_gallery';

    $data['entity_gallery_field_data']['egid']['argument'] = [
      'id' => 'entity_gallery_egid',
      'name field' => 'title',
      'numeric' => TRUE,
      'validate type' => 'egid',
    ];

    $data['entity_gallery_field_data']['title']['field']['default_formatter_settings'] = ['link_to_entity' => TRUE];

    $data['entity_gallery_field_data']['title']['field']['link_to_entity_gallery default'] = TRUE;

    $data['entity_gallery_field_data']['type']['argument']['id'] = 'entity_gallery_type';

    $data['entity_gallery_field_data']['langcode']['help'] = $this->t('The language of the entity galleries or translation.');

    $data['entity_gallery_field_data']['status']['filter']['label'] = $this->t('Published status');
    $data['entity_gallery_field_data']['status']['filter']['type'] = 'yes-no';
    // Use status = 1 instead of status <> 0 in WHERE statement.
    $data['entity_gallery_field_data']['status']['filter']['use_equal'] = TRUE;

    $data['entity_gallery_field_data']['status_extra'] = array(
      'title' => $this->t('Published status or admin user'),
      'help' => $this->t('Filters out unpublished entity galleries if the current user cannot view them.'),
      'filter' => array(
        'field' => 'status',
        'id' => 'entity_gallery_status',
        'label' => $this->t('Published status or admin user'),
      ),
    );

    $data['entity_gallery']['path'] = array(
      'field' => array(
        'title' => $this->t('Path'),
        'help' => $this->t('The aliased path to this entity gallery.'),
        'id' => 'entity_gallery_path',
      ),
    );

    $data['entity_gallery']['entity_gallery_bulk_form'] = array(
      'title' => $this->t('Entity gallery operations bulk form'),
      'help' => $this->t('Add a form element that lets you run operations on multiple entity galleries.'),
      'field' => array(
        'id' => 'entity_gallery_bulk_form',
      ),
    );

    // Bogus fields for aliasing purposes.

    // @todo Add similar support to any date field
    // @see https://www.drupal.org/node/2337507
    $data['entity_gallery_field_data']['created_fulldate'] = array(
      'title' => $this->t('Created date'),
      'help' => $this->t('Date in the form of CCYYMMDD.'),
      'argument' => array(
        'field' => 'created',
        'id' => 'date_fulldate',
      ),
    );

    $data['entity_gallery_field_data']['created_year_month'] = array(
      'title' => $this->t('Created year + month'),
      'help' => $this->t('Date in the form of YYYYMM.'),
      'argument' => array(
        'field' => 'created',
        'id' => 'date_year_month',
      ),
    );

    $data['entity_gallery_field_data']['created_year'] = array(
      'title' => $this->t('Created year'),
      'help' => $this->t('Date in the form of YYYY.'),
      'argument' => array(
        'field' => 'created',
        'id' => 'date_year',
      ),
    );

    $data['entity_gallery_field_data']['created_month'] = array(
      'title' => $this->t('Created month'),
      'help' => $this->t('Date in the form of MM (01 - 12).'),
      'argument' => array(
        'field' => 'created',
        'id' => 'date_month',
      ),
    );

    $data['entity_gallery_field_data']['created_day'] = array(
      'title' => $this->t('Created day'),
      'help' => $this->t('Date in the form of DD (01 - 31).'),
      'argument' => array(
        'field' => 'created',
        'id' => 'date_day',
      ),
    );

    $data['entity_gallery_field_data']['created_week'] = array(
      'title' => $this->t('Created week'),
      'help' => $this->t('Date in the form of WW (01 - 53).'),
      'argument' => array(
        'field' => 'created',
        'id' => 'date_week',
      ),
    );

    $data['entity_gallery_field_data']['changed_fulldate'] = array(
      'title' => $this->t('Updated date'),
      'help' => $this->t('Date in the form of CCYYMMDD.'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_fulldate',
      ),
    );

    $data['entity_gallery_field_data']['changed_year_month'] = array(
      'title' => $this->t('Updated year + month'),
      'help' => $this->t('Date in the form of YYYYMM.'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_year_month',
      ),
    );

    $data['entity_gallery_field_data']['changed_year'] = array(
      'title' => $this->t('Updated year'),
      'help' => $this->t('Date in the form of YYYY.'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_year',
      ),
    );

    $data['entity_gallery_field_data']['changed_month'] = array(
      'title' => $this->t('Updated month'),
      'help' => $this->t('Date in the form of MM (01 - 12).'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_month',
      ),
    );

    $data['entity_gallery_field_data']['changed_day'] = array(
      'title' => $this->t('Updated day'),
      'help' => $this->t('Date in the form of DD (01 - 31).'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_day',
      ),
    );

    $data['entity_gallery_field_data']['changed_week'] = array(
      'title' => $this->t('Updated week'),
      'help' => $this->t('Date in the form of WW (01 - 53).'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_week',
      ),
    );

    $data['entity_gallery_field_data']['uid']['help'] = $this->t('The user authoring the entity gallery. If you need more fields than the uid add the entity gallery: author relationship');
    $data['entity_gallery_field_data']['uid']['filter']['id'] = 'user_name';
    $data['entity_gallery_field_data']['uid']['relationship']['title'] = $this->t('Entity gallery author');
    $data['entity_gallery_field_data']['uid']['relationship']['help'] = $this->t('Relate entity gallery to the user who created it.');
    $data['entity_gallery_field_data']['uid']['relationship']['label'] = $this->t('author');

    $data['entity_gallery']['entity_gallery_listing_empty'] = array(
      'title' => $this->t('Empty Entity Gallery Frontpage behavior'),
      'help' => $this->t('Provides a link to the entity gallery add overview page.'),
      'area' => array(
        'id' => 'entity_gallery_listing_empty',
      ),
    );

    $data['entity_gallery_field_data']['uid_revision']['title'] = $this->t('User has a revision');
    $data['entity_gallery_field_data']['uid_revision']['help'] = $this->t('All entity galleries where a certain user has a revision');
    $data['entity_gallery_field_data']['uid_revision']['real field'] = 'egid';
    $data['entity_gallery_field_data']['uid_revision']['filter']['id'] = 'entity_gallery_uid_revision';
    $data['entity_gallery_field_data']['uid_revision']['argument']['id'] = 'entity_gallery_uid_revision';

    $data['entity_gallery_field_revision']['table']['wizard_id'] = 'entity_gallery_revision';

    // Advertise this table as a possible base table.
    $data['entity_gallery_field_revision']['table']['base']['help'] = $this->t('Entity gallery revision is a history of changes to entity galleries.');
    $data['entity_gallery_field_revision']['table']['base']['defaults']['title'] = 'title';

    $data['entity_gallery_field_revision']['egid']['argument'] = [
      'id' => 'entity_gallery_egid',
      'numeric' => TRUE,
    ];
    // @todo the EGID field needs different behaviour on revision/non-revision
    //   tables. It would be neat if this could be encoded in the base field
    //   definition.
    $data['entity_gallery_field_revision']['egid']['relationship']['id'] = 'standard';
    $data['entity_gallery_field_revision']['egid']['relationship']['base'] = 'entity_gallery_field_data';
    $data['entity_gallery_field_revision']['egid']['relationship']['base field'] = 'egid';
    $data['entity_gallery_field_revision']['egid']['relationship']['title'] = $this->t('Entity gallery');
    $data['entity_gallery_field_revision']['egid']['relationship']['label'] = $this->t('Get the actual entity gallery from an entity gallery revision.');

    $data['entity_gallery_field_revision']['vid'] = array(
      'argument' => array(
        'id' => 'entity_gallery_vid',
        'numeric' => TRUE,
      ),
      'relationship' => array(
        'id' => 'standard',
        'base' => 'entity_gallery_field_data',
        'base field' => 'vid',
        'title' => $this->t('Entity gallery'),
        'label' => $this->t('Get the actual entity gallery from an entity gallery revision.'),
      ),
    ) + $data['entity_gallery_field_revision']['vid'];

    $data['entity_gallery_field_revision']['langcode']['help'] = $this->t('The language the original entity gallery is in.');

    $data['entity_gallery_revision']['revision_uid']['help'] = $this->t('Relate an entity gallery revision to the user who created the revision.');
    $data['entity_gallery_revision']['revision_uid']['relationship']['label'] = $this->t('revision user');

    $data['entity_gallery_field_revision']['table']['wizard_id'] = 'entity_gallery_field_revision';

    $data['entity_gallery_field_revision']['table']['join']['entity_gallery_field_data']['left_field'] = 'vid';
    $data['entity_gallery_field_revision']['table']['join']['entity_gallery_field_data']['field'] = 'vid';

    $data['entity_gallery_field_revision']['status']['filter']['label'] = $this->t('Published');
    $data['entity_gallery_field_revision']['status']['filter']['type'] = 'yes-no';
    $data['entity_gallery_field_revision']['status']['filter']['use_equal'] = TRUE;

    $data['entity_gallery_field_revision']['langcode']['help'] = $this->t('The language of the entity gallery or translation.');

    $data['entity_gallery_field_revision']['link_to_revision'] = array(
      'field' => array(
        'title' => $this->t('Link to revision'),
        'help' => $this->t('Provide a simple link to the revision.'),
        'id' => 'entity_gallery_revision_link',
        'click sortable' => FALSE,
      ),
    );

    $data['entity_gallery_field_revision']['revert_revision'] = array(
      'field' => array(
        'title' => $this->t('Link to revert revision'),
        'help' => $this->t('Provide a simple link to revert to the revision.'),
        'id' => 'entity_gallery_revision_link_revert',
        'click sortable' => FALSE,
      ),
    );

    $data['entity_gallery_field_revision']['delete_revision'] = array(
      'field' => array(
        'title' => $this->t('Link to delete revision'),
        'help' => $this->t('Provide a simple link to delete the entity gallery revision.'),
        'id' => 'entity_gallery_revision_link_delete',
        'click sortable' => FALSE,
      ),
    );

    // Define the base group of this table. Fields that don't have a group defined
    // will go into this field by default.
    $data['entity_gallery_access']['table']['group']  = $this->t('Entity gallery access');

    // For other base tables, explain how we join.
    $data['entity_gallery_access']['table']['join'] = array(
      'entity_gallery_field_data' => array(
        'left_field' => 'egid',
        'field' => 'egid',
      ),
    );
    $data['entity_gallery_access']['egid'] = array(
      'title' => $this->t('Access'),
      'help' => $this->t('Filter by access.'),
      'filter' => array(
        'id' => 'entity_gallery_access',
        'help' => $this->t('Filter for entity gallery by view access. <strong>Not necessary if you are using entity gallery as your base table.</strong>'),
      ),
    );

    // Add search table, fields, filters, etc., but only if a page using the
    // entity_gallery_search plugin is enabled.
    if (\Drupal::moduleHandler()->moduleExists('search')) {
      $enabled = FALSE;
      $search_page_repository = \Drupal::service('search.search_page_repository');
      foreach ($search_page_repository->getActiveSearchpages() as $page) {
        if ($page->getPlugin()->getPluginId() == 'entity_gallery_search') {
          $enabled = TRUE;
          break;
        }
      }

      if ($enabled) {
        $data['entity_gallery_search_index']['table']['group'] = $this->t('Search');

        // Automatically join to the entity gallery table (or actually,
        // entity_gallery_field_data). Use a Views table alias to allow other
        // modules to use this table too, if they use the search index.
        $data['entity_gallery_search_index']['table']['join'] = array(
          'entity_gallery_field_data' => array(
            'left_field' => 'egid',
            'field' => 'sid',
            'table' => 'search_index',
            'extra' => "entity_gallery_search_index.type = 'entity_gallery_search' AND entity_gallery_search_index.langcode = entity_gallery_field_data.langcode",
          )
        );

        $data['entity_gallery_search_total']['table']['join'] = array(
          'entity_gallery_search_index' => array(
            'left_field' => 'word',
            'field' => 'word',
          ),
        );

        $data['entity_gallery_search_dataset']['table']['join'] = array(
          'entity_gallery_field_data' => array(
            'left_field' => 'sid',
            'left_table' => 'entity_gallery_search_index',
            'field' => 'sid',
            'table' => 'search_dataset',
            'extra' => 'entity_gallery_search_index.type = entity_gallery_search_dataset.type AND entity_gallery_search_index.langcode = entity_gallery_search_dataset.langcode',
            'type' => 'INNER',
          ),
        );

        $data['entity_gallery_search_index']['score'] = array(
          'title' => $this->t('Score'),
          'help' => $this->t('The score of the search item. This will not be used if the search filter is not also present.'),
          'field' => array(
            'id' => 'search_score',
            'float' => TRUE,
            'no group by' => TRUE,
          ),
          'sort' => array(
            'id' => 'search_score',
            'no group by' => TRUE,
          ),
        );

        $data['entity_gallery_search_index']['keys'] = array(
          'title' => $this->t('Search Keywords'),
          'help' => $this->t('The keywords to search for.'),
          'filter' => array(
            'id' => 'search_keywords',
            'no group by' => TRUE,
            'search_type' => 'entity_gallery_search',
          ),
          'argument' => array(
            'id' => 'search',
            'no group by' => TRUE,
            'search_type' => 'entity_gallery_search',
          ),
        );

      }
    }

    return $data;
  }

}
