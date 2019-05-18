<?php

namespace Drupal\kashing\form\View;

use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Kashing Form Edit class.
 */
class KashingFormEdit {

  /**
   * Edit page content.
   */
  public function addDeleteFormPage(array &$form) {
    // Table headers.
    $header = [
      'ID' => t('ID'),
      'name' => t('Name'),
      'amount' => t('Amount'),
      'description' => t('Description'),
      'edit' => t('Edit'),
      'delete' => t('Delete'),
    ];

    $form['delete_mode'] = [
      '#type' => 'details',
      '#group' => 'kashing_settings',
      '#title' => t('Edit Forms'),
      '#ajax' => ['callback' => 'Drupal\kashing\form\View\KashingFormEdit::deleteForm', 'wrapper' => 'kashing_columns_edit_wrap'],
    ];

    $options = $this->getTableOptions();

    // TableSelect.
    $form['delete_mode']['table_kashing']['#options'] = $options;
    // TableSelect.
    $form['delete_mode']['table_kashing'] = [
      '#prefix' => '<div id="kashing_columns_edit_wrap">',
      '#type' => 'tableselect',
      '#title' => t('Users'),
      '#title_display' => 'visible',
      '#header' => $header,
      '#options' => isset($options) ? $options : NULL,
      '#empty' => t('No forms found'),
      '#attributes' => ['id' => 'kashing_columns_edit'],
      '#suffix' => '</div>',
    ];

    $form['delete_mode']['kashing_form_delete_button'] = [
      '#type' => 'button',
      '#value' => t('Delete Forms'),
      '#name' => 'kashing_form_delete_button_name',
      '#ajax' => [
        'callback' => 'Drupal\kashing\form\View\KashingFormEdit::deleteForm',
        'wrapper' => 'kashing_columns_edit_wrap',
      ],
      '#suffix' => '<div id="kashing-delete-form-result"></div>',
    ];
  }

  /**
   * Delete Form function.
   */
  public function deleteForm(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();

    $selected_items = $form_state->getValue('table_kashing');

    $info_removed = '<strong>' . t('Removed forms:') . ' </strong><ul>';
    $info_no_action = '<strong>' . t('No forms removed.') . '</strong>';
    $removed_forms = '';

    foreach ($selected_items as $item) {
      $id = $selected_items[$item];
      if ($id) {
        $plugin_name = 'kashing_block';
        $block_entity = $id;
        foreach (entity_load_multiple_by_properties('block', ['plugin' => $plugin_name]) as $block) {
          if ($block->id() == $block_entity) {
            $block->delete();
          }
        }
        $config_name = 'kashing.blocks.forms' . $block_entity;
        \Drupal::configFactory()->getEditable($config_name)->delete();

        $removed_forms .= '<li>' . $id . '</li>';
      }
    }

    if ($removed_forms != '') {
      $ajax_response->addCommand(new HtmlCommand('#kashing-delete-form-result', $info_removed . $removed_forms));
      $ajax_response->addCommand(new InvokeCommand('#kashing-delete-form-result',
        'addClass', ['messages--status messages']));
    }
    else {
      $ajax_response->addCommand(new HtmlCommand('#kashing-delete-form-result', $info_no_action));
      $ajax_response->addCommand(new InvokeCommand('#kashing-delete-form-result',
        'addClass', ['messages--error messages']));
    }

    $renderer = \Drupal::service('renderer');
    $html = $renderer->render($form['delete_mode']['table_kashing']);

    $options = KashingFormEdit::getTableOptions();

    $substring = KashingFormEdit::getHtmlReplacement($html);

    // If any forms left add them.
    if (!empty($options)) {
      $text_body = '';

      foreach ($options as $option) {
        $text_body .= KashingFormEdit::renderTableRow($option['ID'], $option['name'],
          $option['amount'], $option['description']);
      }

      $html = str_replace($substring, $text_body, $html);
    }
    else {
      $text_body = '<tr class="odd"> <td colspan="6" class="empty message">' . t('No forms found') . '</td> </tr>';
    }

    $html = str_replace($substring, $text_body, $html);

    $ajax_response->addCommand(new HtmlCommand('#kashing_columns_edit_wrap', $html));

    return $ajax_response;
  }

  /**
   * Get Table options function.
   */
  public function getTableOptions() {
    $options = [];

    $base_url = Url::fromUri('internal:/')->setAbsolute()->toString();

    $ids = \Drupal::entityQuery('block')->condition('plugin', 'kashing_block')->execute();

    foreach ($ids as $id) {
      $block = entity_load('block', $id);
      $settings = $block->get('settings');

      $name = $settings['label'];
      $amount = $settings['kashing_form_settings']['kashing_form_amount'];
      $description = $settings['kashing_form_settings']['kashing_form_description'];

      $options[$id] = [
        'ID' => $id,
        'name' => $name,
        'amount' => $amount,
        'description' => $description,
        'edit' => [
          'data' => [
            '#markup' => "<a target='_blank' href='" . $base_url . "/admin/structure/block/manage/" .
            $id . "'>" . t('Edit') . "</a>",
          ],
        ],
        'delete' => [
          'data' => [
            '#markup' => "<a target='_blank' href='" . $base_url . "/admin/structure/block/manage/" .
            $id . "/delete'>" . t('Delete') . "</a>",
          ],
        ],
      ];
    }

    return $options;
  }

  /**
   * Reneder table row function.
   */
  public function renderTableRow($id, $name, $amount, $description) {
    $base_url = Url::fromUri('internal:/')->setAbsolute()->toString();

    $render_row = <<<EOT
              <tr class="odd">
              <td>

                <div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-table-kashing-1 form-item-table-kashing-1 form-no-label">
                
                <input id="kashing_columns_edit" data-drupal-selector="edit-table-kashing-1" type="checkbox" name="table_kashing[1]" value="1" class="form-checkbox">

                </div>

                    </td>
                      <td>$id</td>
                      <td>$name</td>
                      <td>$amount</td>
                      <td>$description</td>
                      <td><a target="_blank" href="$base_url/admin/structure/block/manage/$id">Edit</a></td>
                      <td><a target="_blank" href="$base_url/admin/structure/block/manage/$id/delete">Delete</a></td>
               </tr>
EOT;

    return $render_row;
  }

  /**
   * Get HTML replacement function.
   */
  public function getHtmlReplacement($html) {
    $string = $html;
    $start_string = '<tbody>';
    $end_string = '</tbody>';
    $string = ' ' . $string;
    $init_string = strpos($string, $start_string);
    $init_string += strlen($start_string);
    $string_length = strpos($string, $end_string, $init_string) - $init_string;
    $substring = substr($string, $init_string, $string_length);

    return $substring;
  }

}
