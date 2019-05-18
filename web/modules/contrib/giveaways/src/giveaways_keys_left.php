<?php
namespace Drupal\giveaways;

class giveaways_keys_left extends views_handler_field {

  public function query() {
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['gid'] = array('default' => '');
    return $options;
  }

  function options_form(&$form, &$form_state) {
    $form['gid'] = array(
      '#type' => 'textfield',
      '#title' => t('Giveaway Id (replacement token)'),
      '#default_value' => $this->options['gid'],
    );
    parent::options_form($form, $form_state);
  }

  public function render($values) {
    $id = $this->tokenize_value($this->options['gid']);

    $count = db_select('giveaway_keys')
      ->fields(NULL, array('gkid'))
      ->condition('claimed_on', 0)
      ->condition('gid', $id)
      ->execute()
      ->rowCount();

    return $count;
  }
}
