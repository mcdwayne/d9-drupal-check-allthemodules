<?php

namespace Drupal\menu_per_user\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class DeletePath extends FormBase {

  public function getFormId() {
    return 'menu_per_user_delete_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $delete_path = \Drupal::request()->query->get('menu_path');
    $path_data = menu_per_user_check_path($delete_path);
    if ($path_data) {
      $form['output'] = array(
        '#markup' => "Are you sure you want to delete <strong> " . $delete_path . '</strong> redirect? <br><br>',
      );
      $form['delete'] = array(
        '#type' => 'submit',
        '#value' => t('Delete'),
      );
      $form['no'] = array(
        '#type' => 'submit',
        '#value' => t('No'),
      );
      return $form;
    }
    else {
      drupal_set_message(t('Menu Path specified is not correct for deletion.'), 'error');
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if ($values['op']->render() == 'No') {
      menu_per_user_redirect(\Drupal::url('menu_per_user.list_redirects'));
    }
    if ($values['op']->render() == 'Delete') {
      $delete_path = \Drupal::request()->query->get('menu_path');
      if ($delete_path != "<front>") {
        $delete_path = $delete_path;
      }
      $connection = \Drupal::database();
      $connection->delete('user_menu_visibility')
        ->condition('menu_path', $delete_path)
        ->execute();
      drupal_set_message(t("The Menu path '@path' is deleted.", array('@path' => $delete_path)));
      menu_per_user_redirect(\Drupal::url('menu_per_user.list_redirects'));
    }
  }

}
