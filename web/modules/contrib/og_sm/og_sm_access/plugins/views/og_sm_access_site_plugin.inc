<?php
/**
 * @file
 * Views handler to check the access to the currently active Site.
 */

// @codingStandardsIgnoreStart

/**
 * Access plugin that checks access based on the currently active Site.
 */
class og_sm_access_site_plugin extends views_plugin_access {
  /**
   * {@inheritdoc}
   */
  function summary_title() {
    return t('Access to current Site');
  }

  /**
   * {@inheritdoc}
   */
  function access($account) {
    return og_sm_access_callback($account);
  }

  /**
   * {@inheritdoc}
   */
  function __get_access_callback() {
    return array('og_sm_access_callback');
  }
}

// @codingStandardsIgnoreEnd
