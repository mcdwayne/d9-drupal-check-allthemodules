<?php

namespace Drupal\pocket\Action;

class TagRenameAction extends PocketAction {

  const ACTION = 'tag_rename';

  /**
   * TagRenameAction constructor.
   *
   * @param string $old
   * @param string $new
   * @param array  $options
   */
  public function __construct(string $old, string $new, array $options = []) {
    parent::__construct($options);
    $this->set('old_tag', $old)->set('new_tag', $new);
  }

}
