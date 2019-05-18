<?php

namespace Drupal\floating_menu\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Provides a menu block.
 *
 * @Block(
 *   id = "floating_menu_block",
 *   admin_label = @Translation("Floating Menu"),
 * )
 */
class FloatingMenuBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    $menu_item_variables = [];
    for ($i = 0; $i < 5; $i++) {

      $untranslated_url = $config['menu_item'][$i]['menu_item_target_url'];
      if (!empty($untranslated_url) && substr($untranslated_url, 0, 1) == '/') {
        $langManager = \Drupal::service('language_manager');
        $path = \Drupal::service('path.alias_manager')->getPathByAlias($untranslated_url, 'fi');
        $translated_url = \Drupal::service('path.alias_manager')->getAliasByPath($path, $langManager->getCurrentLanguage()->getId());
        if ($langManager->getCurrentLanguage()->getId() != $langManager->getDefaultLanguage()->getId()) {
          $translated_url = '/' . $langManager->getCurrentLanguage()->getId() . $translated_url;
        }
      }
      else {
        $translated_url = $untranslated_url;
      }

      if (!empty($config['menu_item'][$i]['menu_item_icon_url'])) {
        $menu_item_variables[] = [
          'popup_html' => [
            '#markup' => $config['menu_item'][$i]['menu_item_popup_html']['value'],
          ],
          'url' => $translated_url,
          'icon_url' => $config['menu_item'][$i]['menu_item_icon_url'],
        ];
      }
    }
    return [
      '#theme' => 'floating_menu_block',
      '#attached' => [
        'library' => [
          'floating_menu/floating-menu',
        ],
      ],
      '#menu_items' => $menu_item_variables,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['menu_items'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Menu Items'),
      '#prefix' => '<div id="menu-item-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    for ($i = 0; $i < 5; $i++) {
      $form['menu_items']['menu_item_' . $i] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Menu Item') . ' ' . ($i + 1),
        '#tree' => TRUE,
      ];
      $form['menu_items']['menu_item_' . $i]['menu_item_popup_html'] = [
        '#type' => 'text_format',
        '#format' => 'full_html',
        '#title' => $this->t('Menu Item Popup HTML'),
        '#default_value' => $config['menu_item'][$i]['menu_item_popup_html']['value'],
      ];
      $form['menu_items']['menu_item_' . $i]['menu_item_target_url'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Menu Item Target URL'),
        '#default_value' => $config['menu_item'][$i]['menu_item_target_url'],
      ];
      $form['menu_items']['menu_item_' . $i]['menu_item_icon'] = array(
        '#type' => 'managed_file',
        '#title' => $this->t('Menu Item Icon'),
        '#upload_validators' => [
          'file_validate_is_image' => [],
          'file_validate_extensions' => ['jpg jpeg png gif'],
        ],
        '#upload_location' => 'public://',
        '#default_value' => [$config['menu_item'][$i]['menu_item_icon_file_id']],
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $form_state_values = $form_state->getValues();
    $this->configuration['menu_item'] = [];
    foreach ($form_state_values['menu_items'] as $value) {
      $file = File::load($value['menu_item_icon'][0]);
      if (!empty($file)) {
        $file->setPermanent();
        $file->save();
        $image_url = file_create_url($file->getFileUri());
      }
      else {
        $image_url = NULL;
      }
      $this->configuration['menu_item'][] = [
        'menu_item_popup_html' => $value['menu_item_popup_html'],
        'menu_item_target_url' => $value['menu_item_target_url'],
        'menu_item_icon_file_id' => intval($value['menu_item_icon'][0]),
        'menu_item_icon_url' => $image_url,
      ];
    }
  }
  
}
