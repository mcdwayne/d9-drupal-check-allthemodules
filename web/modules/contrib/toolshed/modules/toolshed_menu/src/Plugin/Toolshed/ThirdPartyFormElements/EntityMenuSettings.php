<?php

namespace Drupal\toolshed_menu\Plugin\Toolshed\ThirdPartyFormElements;

use Drupal\core\Form\FormStateInterface;
use Drupal\core\Config\Entity\ConfigEntityInterface;
use Drupal\toolshed\ThirdPartyFormElements;
use Drupal\toolshed_menu\Menu\MenuSelectionTrait;

/**
 * Third party settings form for settings menu posititioning of the entity.
 *
 * @ThirdPartyFormElements(
 *   id = "toolshed_entity_menu",
 *   name = "entity_menu",
 *   label = @Translation("Bundle Menu Trail"),
 *   help = @Translation("Decide where instances of this entity bundle appear virtually in menus."),
 *   entity_types = {
 *     "node_type",
 *     "media_type",
 *     "taxonomy_vocabulary",
 *   },
 * )
 */
class EntityMenuSettings extends ThirdPartyFormElements {

  use MenuSelectionTrait;

  /**
   * {@inheritdoc}
   */
  protected function defaultSettings() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(ConfigEntityInterface $entity, array $parents, FormStateInterface $state) {
    $menuSettings = $this->getSettings($entity);

    $form = [
      '#type' => 'details',
      '#title' => $this->pluginDefinition['label'],
      '#group' => 'additional_settings',
      '#weight' => 5,
      '#description' => $this->t('This manages where the entities of this type and bundle will <em>virtually</em> appear in menus for navigation menu trees. The menus will be built as if the content is in the menu, without creating actual menu links. Which menu is prioritized is controlled by the consumer of this setting.'),

      'menus' => [
        '#type' => 'table',
        '#header' => [
          $this->t('Menu'),
          $this->t('Position'),
        ],
        '#parents' => $parents,
        '#element_validate' => [static::class . '::validateMenuSettings'],
        '#empty' => $this->t('There are currently no menus to assign.'),
      ],
    ];

    foreach ($this->getAvailableMenus() as $menuId => $menu) {
      $form['menus'][$menuId] = [
        'menu_name' => ['#plain_text' => $menu->label()],
        'link' => [
          '#type' => 'select',
          '#options' => $this->getMenuRootOptions($menuId, 'NONE'),
          '#default_value' => !empty($menuSettings[$menuId]) ? $menuSettings[$menuId] : NULL,
        ],
      ];
    }

    return $form;
  }

  /**
   * Check and clear out menus that aren't included.
   *
   * @param array $element
   *   The full table element that is being validated.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the current form.
   */
  public static function validateMenuSettings(array $element, FormStateInterface $form_state) {
    $menus = [];

    foreach ($element['#value'] as $menuId => $values) {
      if (!empty($values['link'])) {
        $menus[$menuId] = $values['link'];
      }
    }

    $form_state->setValueForElement($element, $menus);
  }

}
