<?php

namespace Drupal\beautytips_manager\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class CustomTipsEditForm implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'beautytips_manager_custom_tips_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $tip = beautytips_manager_get_custom_tip($id);
    if (!isset($tip->id)) {
      $tip = NULL;
    }

    $form = [
      '#attached' => [
        'library' => [
          'beautytips/beautytips.beautytips',
        ],
      ],
    ];
    $form['tip'] = [
      '#type' => 'markup',
      '#value' => '',
      '#tree' => TRUE,
    ];
    $form['tip']['id'] = [
      '#type' => 'value',
      '#value' => is_object($tip) ? $tip->id : 0,
    ];
    $form['tip']['element'] = [
      '#type' => 'textfield',
      '#title' => t('Element'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#default_value' => is_object($tip) ? $tip->element : '',
    ];
    $form['tip']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Enabled'),
      '#default_value' => is_object($tip) ? $tip->enabled : TRUE,
    ];
    $content_options = [
      'attribute' => 'attribute',
      'text' => 'text',
      'ajax' => 'ajax',
    ];
    $types = [];
    $types[0] = t('attribute - Enter the attribute of the element that should be displayed. (If empty, the title will be selected.)');
    $types[0] .= '<br />' . t('ex. "alt"');
    $types[1] = t('text - Enter the text that should be displayed with in the beautytip.');
    $types[1] .= '<br />' . t('ex. "This is my beautytip!"');
    $types[2] = t('ajax - This will grab the page from the "href" attribute and display that page.  Enter css selectors to narrow the down the content from that page.');
    $types[2] .= '<br />' . t('ex. "#my-id .my-class"');
    if (\Drupal::currentUser()
      ->hasPermission('use Javascript for custom beautytip display')) {
      $content_options['js'] = 'js';
      $types[3] = 'js - Directly enter javascript to select the content.';
      $types[3] .= '<br />' . t('ex. "$(this).next(\'.description\').html()"');
    }
    $form['tip']['content_type'] = [
      '#type' => 'radios',
      '#title' => t('Type of Content'),
      '#description' => t('This helps determine from where to pull the content to be displayed.'),
      '#options' => $content_options,
      '#default_value' => is_object($tip) ? $tip->content_type : 0,
    ];
    $items = [
      '#theme' => 'item_list',
      '#items' => $types,
    ];
    $form['tip']['content'] = [
      '#type' => 'textarea',
      '#title' => t('Content to Display'),
      '#description' => $items,
      '#default_value' => is_object($tip) ? $tip->content : '',
    ];
    $triggers = beautytips_manager_get_triggers();
    $form['tip']['trigger_on'] = [
      '#type' => 'select',
      '#title' => t('Trigger On'),
      '#description' => t('Not all events are available for all elements. See jQuery <a href="@events">events documentation</a> for details.', ['@events' => 'http://docs.jquery.com/Events']),
      '#options' => $triggers,
      '#default_value' => is_object($tip) ? $tip->trigger_on : 0,
      '#prefix' => '<div class="beautytips-triggers">',
    ];
    $form['tip']['trigger_off'] = [
      '#type' => 'select',
      '#title' => t('Trigger Off'),
      '#options' => $triggers,
      '#suffix' => '</div>',
      '#default_value' => is_object($tip) ? $tip->trigger_off : 0,
    ];

    $form['tip']['disable_link'] = [
      '#type' => 'checkbox',
      '#title' => t('Disable Link Click'),
      '#description' => t('If you have chosen ajax as the type of content, and you would like to prevent the link from working, then check this box.'),
      '#default_value' => is_object($tip) ? $tip->disable_link : 0,
    ];

    $styles = beautytips_get_styles();
    foreach ($styles as $key => $style) {
      $style_options[$key] = $key;
    }
    $form['tip']['style'] = [
      '#type' => 'select',
      '#title' => t('Style'),
      '#options' => $style_options,
      '#default_value' => is_object($tip) ? $tip->style : 'default',
    ];
    $form['tip']['shrink'] = [
      '#type' => 'checkbox',
      '#title' => t('Shrink to Fit'),
      '#description' => t('Shrink the beautytip to the size of the content. This can sometimes help with sizing problems and is good for tips with just one line.'),
      '#default_value' => is_object($tip) ? $tip->shrink : FALSE,
    ];

    $positions = is_object($tip) ? explode(',', $tip->positions) : [];
    $form['tip']['positions'] = [
      '#type' => 'fieldset',
      '#title' => t('Positions'),
      '#description' => t("Optionally enter the order of positions in which you want the tip to appear.  It will use first in order with available space. The last value will be used if others don't have enough space. If no entries, then the tip will be placed in the area with the most space. Only enter an order for those you wish to use"),
      '#tree' => TRUE,
    ];
    $positions_list = ['top', 'bottom', 'left', 'right'];
    foreach ($positions_list as $position) {
      $form['tip']['positions'][$position] = [
        '#type' => 'textfield',
        '#title' => t($position),
        '#default_value' => (array_search($position, $positions) !== FALSE) ? array_search($position, $positions) : '',
        '#size' => 1,
        '#maxlength' => 1,
        '#prefix' => '<div class="beautytips-positions">',
        '#suffix' => '</div>',
      ];
    }

    $form['tip']['animation_on'] = [
      '#type' => 'select',
      '#title' => t('Animation (On)'),
      '#options' => ['' => '<none>', 'fadeIn' => 'fadeIn'],
      '#description' => t("These animations will be applied to the tip when it is turn on or off."),
      '#default_value' => is_object($tip) ? $tip->animation_on : '',
      '#prefix' => '<div class="beautytips-animations">',
    ];
    $form['tip']['animation_off'] = [
      '#type' => 'select',
      '#title' => t('Animation (Off)'),
      '#options' => [
        '' => '<none>',
        'fadeOut' => 'fadeOut',
        'slideOut' => 'slideOut',
      ],
      '#default_value' => is_object($tip) ? $tip->animation_off : '',
      '#suffix' => '</div>',
    ];

    $options = [
      t('Show on every page except the listed pages.'),
      t('Show on only the listed pages.'),
    ];
    $description = t("Enter one page per line as Drupal paths. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", [
      '%blog' => 'blog',
      '%blog-wildcard' => 'blog/*',
      '%front' => '<front>',
    ]);

    $form['tip']['visibility'] = [
      '#type' => 'radios',
      '#title' => t('Show beautytip on specific pages'),
      '#options' => $options,
      '#default_value' => is_object($tip) ? $tip->visibility : 0,
      '#prefix' => '<div id="edit-tip-visibility-wrapper">',
      '#suffix' => '</div>',
    ];
    $form['tip']['pages'] = [
      '#type' => 'textarea',
      '#title' => t('Pages'),
      '#default_value' => is_object($tip) ? $tip->pages : '',
      '#description' => $description,
    ];

    $form['save'] = [
      '#type' => 'submit',
      '#value' => t('Save configuration'),
    ];
    $form['delete'] = [
      '#type' => 'link',
      '#title' => t('Delete'),
      '#attributes' => ['class' => ['button']],
      '#url' => Url::fromUserInput("/admin/config/user-interface/beautytips/custom-tips/$id/delete", ['query' => ['destination' => "admin/config/user-interface/beautytips/custom-tips/$id/edit"]]),
      '#access' => ($id) ? TRUE : FALSE,
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $tip = $form_state->getValue('tip');
    $positions = $tip['position'];
    foreach ($positions as $position => $order) {
      if ($order !== '' && !is_numeric($order)) {
        $form_state->setErrorByName("tip][positions][$position", t('You must enter a numeric value for position order (Or leave it blank).'));
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $tip = $form_state->getValue('tip');
    $positions = [];
    foreach ($tip['positions'] as $position => $order) {
      if ($order !== '') {
        while (isset($positions[$order])) {
          $order++;
        }
        $positions[$order] = $position;
      }
    }
    ksort($positions);
    $tip['positions'] = (count($positions)) ? implode(',', $positions) : '';

    beautytips_manager_save_custom_tip($tip);
    \Drupal::cache()->delete('beautytips:beautytips-ui-custom-tips');
    $form_state->setRedirect('beautytips_manager.customTips');
  }
}
