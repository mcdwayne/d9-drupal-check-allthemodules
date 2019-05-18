<?php

namespace Drupal\amswap\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AmswapConfigForm.
 *
 * @package Drupal\amswap\Form
 */
class AmswapConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'amswap.amswapconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'amswap_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // kint($form, '$form');
    // kint($form_state, '$form_state');

    $config = $this->config('amswap.amswapconfig');

    // Prepare role selector options
    $roles = \Drupal::entityTypeManager()
      ->getStorage('user_role')
      ->loadMultiple();
    // Add the first instructional option
    $role_options = ['' => $this->t('- Select a role -')];
    foreach ($roles as $role) {
      $role_options[$role->id()] = $role->label();
    }

    // Prepare menu selector options
    $menus = \Drupal::entityTypeManager()->getStorage('menu')->loadMultiple();
    // Add the first instructional option
    $menu_options = ['' => $this->t('- Select a menu -')];
    foreach ($menus as $menu) {
      $menu_options[$menu->id()] = $menu->label();
    }

    // @deprecated Old text field
    // $form['role_menu_pairs'] = [
    //   '#type' => 'textarea',
    //   '#title' => $this->t('Role &amp; menu pairs'),
    //   '#description' => $this->t('Roles and their associated menus. Eg &quot;owner:owner-menu; ...&quot;'),
    //   '#default_value' => $config->get('role_menu_pairs'),
    // ];

    $role_menu_pairs = $config->get('role_menu_pairs');
    // kint(['test'], '$role_menu_pairs');
    // var_dump($role_menu_pairs);

    $num_pairs = $form_state->get('num_pairs');
    $num_pairs = $num_pairs ? $num_pairs : 1;

    // var_dump($num_pairs);

    // Use the larger of pairs saved or pairs added using the button
    $num_pairs_in_form = count($role_menu_pairs) > $num_pairs ? count($role_menu_pairs) : $num_pairs;
    $form_state->set('num_pairs', $num_pairs_in_form);

    for ($i=0; $i < $num_pairs_in_form; $i++) {
      $form['role_menu_pairs'][$i] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Role-Menu Pair ' . ($i + 1)),
      ];
      $role = isset($role_menu_pairs[$i]) ? $role_menu_pairs[$i]['role'] : NULL;
      $form['role_menu_pairs'][$i]['pair-' . $i . '-role'] = [
        '#type' => 'select',
        '#title' => $this->t('Role'),
        '#description' => $this->t('Select a role.'),
        '#default_value' => $role,
        '#options' => $role_options,
      ];
      $menu = isset($role_menu_pairs[$i]) ? $role_menu_pairs[$i]['menu'] : NULL;
      $form['role_menu_pairs'][$i]['pair-' . $i . '-menu'] = [
        '#type' => 'select',
        '#title' => $this->t('Menu'),
        '#description' => $this->t('Select which menu should be displayed for that role.'),
        '#default_value' => $menu,
        '#options' => $menu_options,
      ];
      $form['role_menu_pairs'][$i]['pair-' . $i . '-delete'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove ' . ($i + 1)),
        '#submit' => ['::amswap_delete_pair'],
        '#attributes' => ['pair_num' => $i],
      ];
    }

    $form['add_pair'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another role-menu pair'),
      '#submit' => ['::amswap_add_pair'],
    ];


    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function amswap_add_pair(array $form, FormStateInterface &$form_state) {
    // Get the current number of pairs, or 1 if not provided
    $num_pairs = $form_state->get('num_pairs');
    $num_pairs = $num_pairs ? $num_pairs : 1;
    // Add 1 to the number of role-menu pairs that should be displayed
    $form_state->set('num_pairs', $num_pairs + 1);

    // Set the form to be rebuilt.
    $form_state->setRebuild(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function amswap_delete_pair(array $form, FormStateInterface &$form_state) {
    // kint($form_state, '$form_state');
    $button = $form_state->getTriggeringElement();
    // kint($button, '$button');
    $item = $button['#attributes']['pair_num'];
    $form_state->unsetValue('pair-' . $item . '-role');
    $form_state->unsetValue('pair-' . $item . '-menu');

    $msg = t('Pair ' . ($item + 1) . ' removed. Other pairs saved.');
    drupal_set_message($msg, 'status', FALSE);

    $this->submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $this->checkForDuplicates($form, $form_state);
  }

  public function checkForDuplicates(array &$form, FormStateInterface $form_state) {
    // Get trigger.
    $trigger = $form_state->getTriggeringElement();
    // Check if pair is being deleted, if so; skip the validation for that pair.
    $skip = NULL;
    if (strpos($trigger['#id'], 'delete') !== FALSE) {
      $skip = $trigger['#attributes']['pair_num'];
    }
    
    // Set up variables for the pairs.
    $pairs = [];
    $num_pairs = $form_state->get('num_pairs');
    // Ensure number of pairs is always at least 1
    $num_pairs = $num_pairs ? $num_pairs : 1;

    // Loop through all pairs to find duplicates.
    for ($i = 0; $i < $num_pairs; $i++) {
      // If skip has been set, skip this item.
      if ($i === $skip) {
        continue;
      }
      $role = $form_state->getValue('pair-' . $i . '-role');
      $menu = $form_state->getValue('pair-' . $i . '-menu');
      // Save first pair for this role.
      if (!array_key_exists($role, $pairs)) {
        $pairs[$role] = [$menu];
      }
      else {
        // If pair already exists; set error message.
        if (in_array($menu, $pairs[$role])) {
          $msg = t('Pair @item is a duplicate.', ['@item' => ($i + 1)]);
          $form_state->setErrorByName('pair-' . $i . '-role', $msg);
          $form_state->setErrorByName('pair-' . $i . '-menu');
        }
        else {
          // Save this combination of role and menu.
          $pairs[$role][] = $menu;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // kint($form, '$form');
    // kint($form_state, '$form_state');

    parent::submitForm($form, $form_state);

    $trigger = $form_state->getTriggeringElement();
    // kint($trigger, '$trigger');

    $pairs = [];
    $pair_index = 0;
    $num_pairs = $form_state->get('num_pairs');
    $num_pairs = $num_pairs ? $num_pairs : 1;
    // kint($num_pairs, '$num_pairs');
    for ($i=0; $i < $num_pairs; $i++) {
    //   $pairs[$] = $i;
      $role = $form_state->getValue('pair-' . $i . '-role');
      $menu = $form_state->getValue('pair-' . $i . '-menu');
      if ($role && $menu) {
        $pairs[$pair_index]['role'] = $role;
        $pairs[$pair_index]['menu'] = $menu;
        $pair_index ++;
      }
      // Otherwise if not triggered by a delete button
      elseif (strpos($trigger['#id'], 'delete') === FALSE) {
        $msg = t('Pair ' . ($i + 1) . ' was missing either a role or a menu value, so was not saved.');
        drupal_set_message($msg, 'warning', FALSE);
      }
    }
    // kint($pairs, '$pairs');

    $this->config('amswap.amswapconfig')
      ->set('role_menu_pairs', $pairs)
      ->save();

    $url = \Drupal\Core\Url::fromRoute('system.performance_settings');
    // kint($url, '$url');
    $link = \Drupal\Core\Link::fromTextAndUrl('Clear caches', $url);
    // kint($link, '$link');
    $msg = $link->toString() . ' to see the changes.';
    $rendered_msg = \Drupal\Core\Render\Markup::create($msg);
    drupal_set_message($rendered_msg, 'status', FALSE);

    // drupal_flush_all_caches();
  }

}
