<?php

/**
 * @file
 * Definition of og_sm_user_views_handler_user_name.
 */

/**
 * Field handler to provide a themed user link.
 *
 * This will link to the user profile page within a Site when a Site context
 * is active.
 *
 * @ingroup views_field_handlers
 */
class og_sm_user_views_handler_field_user_name extends views_handler_field_user_name {

  /**
   * {@inheritdoc}
   */
  public function init(&$view, &$data) {
    // Add uid in the query so we can test for anonymous if needed.
    parent::init($view, $data);
    if (!empty($this->options['link_to_user'])) {
      $this->additional_fields['status'] = 'status';
    }
  }

  /**
   * {@inheritdoc}
   */
  // @codingStandardsIgnoreStart
  public function render_link($data, $values) {
  // @codingStandardsIgnoreEnd
    // Only alter if we need to show a link.
    if (empty($this->options['link_to_user'])) {
      return parent::render_link($data, $values);
    }

    // Only alter if we are within an active Site context.
    $site = og_sm_current_site();
    if (!$site) {
      return parent::render_link($data, $values);
    }

    // Create a minimal object needed to format & check access.
    $account = new stdClass();
    $account->name = $this->get_value($values);
    $account->uid = $this->get_value($values, 'uid');
    $account->status = $this->get_value($values, 'status');
    $name = format_username($account);

    // Check if the user can see the user account.
    if (!og_sm_user_page_access('view', $site, $account)) {
      return $this->sanitize_value($name);
    }

    return l(
      $this->sanitize_value($name),
      sprintf(
        'group/node/%d/profile/%d',
        $site->nid,
        $this->get_value($values, 'uid')
      ),
      array(
        'html' => TRUE,
      )
    );
  }

}
