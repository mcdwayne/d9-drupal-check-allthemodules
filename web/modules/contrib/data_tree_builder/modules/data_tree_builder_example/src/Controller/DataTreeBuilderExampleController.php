<?php

namespace Drupal\data_tree_builder_example\Controller;

use Drupal\data_tree_builder\Controller\DataTreeBuilderAjaxBase;
use Drupal\data_tree_builder_example\Form\DataTreeBuilderExampleForm;

class DataTreeBuilderExampleController extends DataTreeBuilderAjaxBase {
  /**
   * {@inheritdoc}
   */
  const FORM_CLASS = DataTreeBuilderExampleForm::class;
}