<?php
/**
 * Implements hook_views_data().
 */
function newsletter_digest_views_data() { 
 $data = array();

    $data['newsletter_digest_subscriber']['table']['group'] = t('Newsletter Digest Subscriber');

    $data['newsletter_digest_subscriber']['table']['base'] = array(
        'title' => t('Newsletter Digest Subscriber'),
        'help' => t('Contains records we want exposed to Views.'),
    );

        // The ID field
    $data['newsletter_digest_subscriber']['id'] = array(
        'title' => t('ID'),
        'help' => t('The record ID.'),
        'field' => array(
            'handler' => 'views_handler_field_numeric',
        ),        
    );

    // The Name field
    $data['newsletter_digest_subscriber']['first_name'] = array(
        'title' => t('First Name'),
        'help' => t('The record First name.'),
        'field' => array(
            'handler' => 'views_handler_field',
        ),
        'sort' => array(
            'handler' => 'views_handler_sort',
        ),
        'filter' => array(
            'handler' => 'views_handler_filter_string',
        ),
    );

    $data['newsletter_digest_subscriber']['last_name'] = array(
        'title' => t('Last Name'),
        'help' => t('The record Last name.'),
        'field' => array(
            'handler' => 'views_handler_field',
        ),
        'sort' => array(
            'handler' => 'views_handler_sort',
        ),
        'filter' => array(
            'handler' => 'views_handler_filter_string',
        ),
    );

    $data['newsletter_digest_subscriber']['email'] = array(
        'title' => t('Email'),
        'help' => t('The record Email.'),
        'field' => array(
            'handler' => 'views_handler_field',
        ),
        'sort' => array(
            'handler' => 'views_handler_sort',
        ),
        'filter' => array(
            'handler' => 'views_handler_filter_string',
        ),
    );

    $data['newsletter_digest_subscriber']['category_id'] = array(
        'title' => t('Category Id'),
        'help' => t('The record Category ID.'),
        'field' => array(
            'handler' => 'views_handler_field',
        ),
        'sort' => array(
            'handler' => 'views_handler_sort',
        ),
        'filter' => array(
            'handler' => 'views_handler_filter_string',
        ),
    );

    $data['newsletter_digest_subscriber']['status'] = array(
        'title' => t('Status'),
        'help' => t('The record Status.'),
        'field' => array(
            'handler' => 'views_handler_field',
        ),
        'sort' => array(
            'handler' => 'views_handler_sort',
        ),
        'filter' => array(
            'handler' => 'views_handler_filter_string',
        ),
    );

    $data['newsletter_digest_subscriber']['created'] = array(
        'title' => t('Created Time'),
        'help' => t('Created Time'),
        'field' => array(
            'handler' => 'views_handler_field',
        ),
        'sort' => array(
            'handler' => 'views_handler_sort',
        ),
        'filter' => array(
            'handler' => 'views_handler_filter_string',
        ),
    );

    return $data;
}
?>