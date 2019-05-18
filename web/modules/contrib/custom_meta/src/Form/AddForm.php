<?php

/**
 * @file
 * Contains \Drupal\custom_meta\Form\AddForm.
 */

namespace Drupal\custom_meta\Form;

/**
 * Provides the custom meta tag add form.
 */
class AddForm extends CustomMetaFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_meta_admin_add';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildPath($meta_uid) {
    return array(
      'meta_uid' => NULL,
      'meta_attr' => '',
      'meta_attr_value' => '',
      'meta_content' => '',
    );
  }
}
