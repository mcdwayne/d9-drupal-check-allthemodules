<?php
/**
 * @file
 * Provides theme functions for Project Management modules.
 */

/**
 * Provides HTML for a Project Management dashboard.
 */
function theme_pm_dashboard($vars) {
  $link_blocks = $vars['links'];
  $content = '<div id="pmdashboard">';
  if (!empty($link_blocks)) {
    $content .= '<dl class="pmdashboard clear-block">';
    foreach ($link_blocks as $link_block_array) {
      $content .= '<div class="pmdashboard">';
      if (!empty($link_block_array) AND is_array($link_block_array)) {
        foreach ($link_block_array as $link_array) {
          if (!empty($link_array['theme'])) {
            $content .= theme($link_array['theme'], array('link_blocks' => $link_array));
          }
          else {
            $content .= theme('pm_dashboard_link', array('link_blocks' => $link_array));
          }
        }
      }
      $content .= '</div>';
    }
    $content .= '</dl>';
  }
  else {
    $content .= t('No dashboard links available');
  }
  $content .= '</div>';

  return $content;
}

/**
 * Provides HTML for the Project Management dashboard blocks.
 */
function theme_pm_dashboard_block($vars) {
  $link_blocks = $vars['links'];
  $content = '<div id="pmdashboard-block">';
  if (!empty($link_blocks)) {
    foreach ($link_blocks as $link_array) {
      if (!empty($link_array['theme'])) {
        $content .= theme($link_array['theme'], array('link_blocks' => $link_array));
      }
      else {
        $content .= theme('pm_dashboard_link', array('link_blocks' => $link_array));
      }
    }
  }
  else {
    // No links, hide block.
    return '';
  }
  $content .= '</div>';
  return $content;
}

/**
 * Provides HTML for Project Management links.
 */
function theme_pm_dashboard_link($vars) {
  $link_array = $vars['link_blocks'];
  $content = '';

  // Default icon.
  if (empty($link_array['icon'])) {
    $dt_id = 'pmexpenses';
  }
  else {
    $dt_id = $link_array['icon'];
  }

  $params = array();
  if (!empty($link_array['nid'])) {
    $params_key = $link_array['node_type'] . '_nid';
    $params['query'] = array($params_key => $link_array['nid']);
  }

  $link = l($link_array['title'], $link_array['path'], $params);

  // ADD PLUS SIGN (node/add)
  if (!empty($link_array['add_type'])) {
    $item = new stdClass();
    $item->type = $link_array['add_type'];
    if (empty($link_array['params'])) {
      $link_array['params'] = array();
    }
    $link .= pm_icon_add('node/add/' . str_replace('_', '-', $link_array['add_type']), $item, $link_array['params']);
  }

  if (empty($link_array['nid']) || 0 == $link_array['nid']) {
    if (variable_get('pm_icon', PM_ICON_SET_DEFAULT_BEHAVIOUR) !== PM_ICON_SET_NO_ICON) {
      $content .= '<dt class="pmcomponent">' . pm_helper_get_fa_icon($dt_id);
    }
    else {
      $content .= '<dt class="pmcomponent">';
    }
    $content .= $link;
    $content .= '</dt>';
  }
  else {
    $content = array(
      '#prefix' => (variable_get('pm_icon', PM_ICON_SET_DEFAULT_BEHAVIOUR) !== PM_ICON_SET_NO_ICON) ? '<dt class="pmcomponent">' . pm_helper_get_fa_icon($dt_id) : '<dt class="pmcomponent">',
      '#suffix' => '</dt>',
      '#value' => $link,
      '#weight' => $link_array['weight'],
    );
  }
  return $content;
}
