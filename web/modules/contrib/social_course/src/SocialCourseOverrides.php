<?php

namespace Drupal\social_course;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialCourseOverrides.
 */
class SocialCourseOverrides implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    $config_name = 'views.view.upcoming_events';

    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'display' => [
          'upcoming_events_group' => [
            'display_options' => [
              'arguments' => [
                'gid' => [
                  'specify_validation' => TRUE,
                  'validate' => [
                    'type' => 'entity:group',
                  ],
                  'validate_options' => [
                    'access' => '',
                    'bundles' => [
                      'course_advanced' => 'course_advanced',
                      'open_group' => 'open_group',
                      'closed_group' => 'closed_group',
                      'public_group' => 'public_group',
                    ],
                    'multiple' => FALSE,
                    'operation' => 'view',
                  ],
                ],
              ],
            ],
          ],
        ],
      ];
    }

    $config_name = 'views.view.group_events';

    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'display' => [
          'default' => [
            'display_options' => [
              'arguments' => [
                'gid' => [
                  'specify_validation' => TRUE,
                  'validate' => [
                    'type' => 'entity:group',
                  ],
                  'validate_options' => [
                    'access' => '',
                    'bundles' => [
                      'course_advanced' => 'course_advanced',
                      'open_group' => 'open_group',
                      'closed_group' => 'closed_group',
                      'public_group' => 'public_group',
                    ],
                    'multiple' => FALSE,
                    'operation' => 'view',
                  ],
                ],
              ],
              'filters' => [
                'type' => [
                  'value' => [
                    'open_group-group_node-event' => 'open_group-group_node-event',
                    'public_group-group_node-event' => 'public_group-group_node-event',
                    'closed_group-group_node-event' => 'closed_group-group_node-event',
                    'course_advanced-group_node-event' => 'course_advanced-group_node-event',
                  ],
                ],
              ],
            ],
          ],
        ],
      ];
    }

    $config_name = 'views.view.group_members';

    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'display' => [
          'default' => [
            'display_options' => [
              'arguments' => [
                'gid' => [
                  'specify_validation' => TRUE,
                  'validate' => [
                    'type' => 'entity:group',
                  ],
                  'validate_options' => [
                    'access' => '',
                    'bundles' => [
                      'course_advanced' => 'course_advanced',
                      'open_group' => 'open_group',
                      'closed_group' => 'closed_group',
                      'public_group' => 'public_group',
                    ],
                    'multiple' => FALSE,
                    'operation' => 'view',
                  ],
                ],
              ],
              'filters' => [
                'type' => [
                  'value' => [
                    'open_group-group_membership' => 'open_group-group_membership',
                    'public_group-group_membership' => 'public_group-group_membership',
                    'closed_group-group_membership' => 'closed_group-group_membership',
                    'course_advanced-group_membership' => 'course_advanced-group_membership',
                  ],
                ],
              ],
            ],
          ],
        ],
      ];
    }

    $config_name = 'views.view.group_topics';

    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'display' => [
          'default' => [
            'display_options' => [
              'arguments' => [
                'gid' => [
                  'specify_validation' => TRUE,
                  'validate' => [
                    'type' => 'entity:group',
                  ],
                  'validate_options' => [
                    'access' => '',
                    'bundles' => [
                      'course_advanced' => 'course_advanced',
                      'open_group' => 'open_group',
                      'closed_group' => 'closed_group',
                      'public_group' => 'public_group',
                    ],
                    'multiple' => FALSE,
                    'operation' => 'view',
                  ],
                ],
              ],
              'filters' => [
                'type' => [
                  'value' => [
                    'open_group-group_node-event' => 'open_group-group_node-event',
                    'public_group-group_node-event' => 'public_group-group_node-event',
                    'closed_group-group_node-event' => 'closed_group-group_node-event',
                    'course_advanced-group_node-event' => 'course_advanced-group_node-event',
                  ],
                ],
              ],
            ],
          ],
        ],
      ];
    }

    $config_name = 'views.view.latest_topics';

    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'display' => [
          'group_topics_block' => [
            'display_options' => [
              'arguments' => [
                'gid' => [
                  'specify_validation' => TRUE,
                  'validate' => [
                    'type' => 'entity:group',
                  ],
                  'validate_options' => [
                    'access' => '',
                    'bundles' => [
                      'course_advanced' => 'course_advanced',
                      'open_group' => 'open_group',
                      'closed_group' => 'closed_group',
                      'public_group' => 'public_group',
                    ],
                    'multiple' => FALSE,
                    'operation' => 'view',
                  ],
                ],
              ],
            ],
          ],
        ],
      ];
    }

    $config_name = 'views.view.newest_groups';

    if (in_array($config_name, $names)) {
      $display_options = [
        'display_options' => [
          'filters' => [
            'type_1' => [
              'id' => 'type',
              'table' => 'groups_field_data',
              'field' => 'type',
              'relationship' => 'none',
              'group_type' => 'group',
              'admin_label' => '',
              'operator' => 'not in',
              'value' => [
                'course_advanced' => 'course_advanced',
                'course_basic' => 'course_basic',
              ],
              'group' => 1,
              'exposed' => FALSE,
              'expose' => [
                'operator_id' => '',
                'label' => '',
                'description' => '',
                'use_operator' => FALSE,
                'operator' => '',
                'identifier' => '',
                'required' => FALSE,
                'remember' => FALSE,
                'multiple' => FALSE,
                'remember_roles' => [
                  'authenticated' => 'authenticated',
                ],
                'reduce' => FALSE,
              ],
              'is_grouped' => FALSE,
              'group_info' => [
                'label' => '',
                'description' => '',
                'identifier' => '',
                'optional' => TRUE,
                'widget' => 'select',
                'multiple' => FALSE,
                'remember' => FALSE,
                'default_group' => 'All',
                'default_group_multiple' => [],
                'group_items' => [],
              ],
              'entity_type' => 'group',
              'entity_field' => 'type',
              'plugin_id' => 'bundle',
            ],
          ],
        ],
      ];

      $overrides[$config_name] = [
        'display' => [
          'page_all_groups' => $display_options,
          'default' => $display_options,
        ],
      ];
    }

    $config_name = 'views.view.groups';

    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'display' => [
          'default' => [
            'display_options' => [
              'filters' => [
                'type' => [
                  'id' => 'type',
                  'table' => 'groups_field_data',
                  'field' => 'type',
                  'relationship' => 'none',
                  'group_type' => 'group',
                  'admin_label' => '',
                  'operator' => 'not in',
                  'value' => [
                    'course_advanced' => 'course_advanced',
                    'course_basic' => 'course_basic',
                  ],
                  'group' => 1,
                  'exposed' => FALSE,
                  'expose' => [
                    'operator_id' => '',
                    'label' => '',
                    'description' => '',
                    'use_operator' => FALSE,
                    'operator' => '',
                    'identifier' => '',
                    'required' => FALSE,
                    'remember' => FALSE,
                    'multiple' => FALSE,
                    'remember_roles' => [
                      'authenticated' => 'authenticated',
                    ],
                    'reduce' => FALSE,
                  ],
                  'is_grouped' => FALSE,
                  'group_info' => [
                    'label' => '',
                    'description' => '',
                    'identifier' => '',
                    'optional' => TRUE,
                    'widget' => 'select',
                    'multiple' => FALSE,
                    'remember' => FALSE,
                    'default_group' => 'All',
                    'default_group_multiple' => [],
                    'group_items' => [],
                  ],
                  'entity_type' => 'group',
                  'entity_field' => 'type',
                  'plugin_id' => 'bundle',
                ],
              ],
            ],
          ],
        ],
      ];
    }

    $config_name = 'views.view.search_groups';

    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'display' => [
          'default' => [
            'display_options' => [
              'filters' => [
                'type' => [
                  'admin_label' => '',
                  'expose' => [
                    'description' => '',
                    'identifier' => '',
                    'label' => '',
                    'multiple' => FALSE,
                    'operator' => '',
                    'operator_id' => '',
                    'remember' => FALSE,
                    'remember_roles' => [
                      'authenticated' => 'authenticated',
                    ],
                    'required' => FALSE,
                    'use_operator' => FALSE,
                  ],
                  'exposed' => FALSE,
                  'field' => 'type',
                  'group' => 1,
                  'group_info' => [
                    'default_group' => 'All',
                    'description' => '',
                    'identifier' => '',
                    'label' => '',
                    'multiple' => FALSE,
                    'optional' => TRUE,
                    'remember' => FALSE,
                    'widget' => 'select',
                  ],
                  'group_type' => 'group',
                  'id' => 'type',
                  'is_grouped' => FALSE,
                  'operator' => '!=',
                  'plugin_id' => 'search_api_string',
                  'relationship' => 'none',
                  'table' => 'search_api_index_social_groups',
                  'value' => [
                    'max' => '',
                    'min' => '',
                    'value' => 'course_advanced',
                  ],
                ],
                'type_1' => [
                  'admin_label' => '',
                  'expose' => [
                    'description' => '',
                    'identifier' => '',
                    'label' => '',
                    'multiple' => FALSE,
                    'operator' => '',
                    'operator_id' => '',
                    'remember' => FALSE,
                    'remember_roles' => [
                      'authenticated' => 'authenticated',
                    ],
                    'required' => FALSE,
                    'use_operator' => FALSE,
                  ],
                  'exposed' => FALSE,
                  'field' => 'type',
                  'group' => 1,
                  'group_info' => [
                    'default_group' => 'All',
                    'description' => '',
                    'identifier' => '',
                    'label' => '',
                    'multiple' => FALSE,
                    'optional' => TRUE,
                    'remember' => FALSE,
                    'widget' => 'select',
                  ],
                  'group_type' => 'group',
                  'id' => 'type',
                  'is_grouped' => FALSE,
                  'operator' => '!=',
                  'plugin_id' => 'search_api_string',
                  'relationship' => 'none',
                  'table' => 'search_api_index_social_groups',
                  'value' => [
                    'max' => '',
                    'min' => '',
                    'value' => 'course_basic',
                  ],
                ],
              ],
            ],
          ],
        ],
      ];
    }

    $config_names = [
      'search_api.index.social_all',
      'search_api.index.social_content',
    ];

    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $overrides[$config_name] = [
          'datasource_settings' => [
            'entity:node' => [
              'bundles' => [
                'selected' => [
                  'course_article',
                  'course_section',
                  'course_video',
                ],
              ],
            ],
          ],
        ];

        if ($config_name === 'search_api.index.social_all') {
          $overrides[$config_name]['field_settings']['group_status'] = [
            'label' => 'Published',
            'datasource_id' => 'entity:group',
            'property_path' => 'status',
            'type' => 'boolean',
          ];
        }
      }
    }

    // Set view mode "Teaser" for "Course" groups in Search All.
    $config_name = 'views.view.search_all';

    if (in_array($config_name, $names)) {
      $config = \Drupal::service('config.factory')->getEditable($config_name);
      $bundles = $config->get('display.default.display_options.row.options.view_modes.entity:group');
      $bundles['course_basic'] = 'teaser';
      $bundles['course_advanced'] = 'teaser';

      if (in_array($config_name, $names)) {
        $overrides[$config_name] = [
          'display' => [
            'default' => [
              'display_options' => [
                'row' => [
                  'options' => [
                    'view_modes' => [
                      'entity:group' => $bundles,
                    ],
                  ],
                ],
              ],
            ],
          ],
        ];
      }
    }

    $config_name = 'views.view.group_managers';

    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'display' => [
          'default' => [
            'display_options' => [
              'filters' => [
                'group_roles_target_id' => [
                  'operator' => 'ends',
                  'value' => 'group_manager',
                ],
              ],
            ],
          ],
        ],
      ];
    }

    $config_name = 'block.block.views_block__group_managers_block_list_managers';

    if (in_array($config_name, $names)) {
      $config = \Drupal::service('config.factory')->getEditable($config_name);
      $group_types = $config->get('visibility.group_type.group_types');
      $group_types['course_basic'] = 'course_basic';
      $group_types['course_advanced'] = 'course_advanced';
      $overrides[$config_name] = [
        'visibility' => [
          'group_type' => [
            'group_types' => $group_types,
          ],
        ],
      ];
    }

    // Show profile hero block on My Courses page.
    $config_name = 'block.block.socialblue_profile_hero_block';

    if (in_array($config_name, $names)) {
      $config = \Drupal::service('config.factory')->getEditable($config_name);
      $pages = $config->get('visibility.request_path.pages');
      $pages .= "\r\n/user/*/courses";
      $overrides[$config_name] = [
        'visibility' => [
          'request_path' => [
            'pages' => $pages,
          ],
        ],
      ];
    }

    // Add Basic and Advanced Courses to related courses field settings.
    $config_names = [
      'field.field.group.course_advanced.field_course_related_courses',
      'field.field.group.course_basic.field_course_related_courses',
    ];

    foreach ($names as $name) {
      if (in_array($name, $config_names)) {
        $overrides[$name] = [
          'settings' => [
            'handler_settings' => [
              'target_bundles' => [
                'course_advanced',
                'course_basic',
              ],
            ],
          ],
        ];
      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialCourseOverrider';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
