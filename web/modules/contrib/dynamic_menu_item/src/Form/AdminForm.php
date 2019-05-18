<?php
/**
 * @file
 * Contains Drupal\dynamic_menu_item\Form\AdminForm
 */
namespace Drupal\dynamic_menu_item\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class AdminForm extends ConfigFormBase {
    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'dynamic_menu_item.adminsettings',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'dynamic_menu_item_admin_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('dynamic_menu_item.adminsettings');

        /** @var \Drupal\Core\Menu\MenuParentFormSelectorInterface $menu_parent_selector */
        $menu_parent_selector = \Drupal::service('menu.parent_form_selector');
        $menu_names = menu_ui_get_menus();
        $parent_element = $menu_parent_selector->parentSelectElement($config->get('menu_parent'), '', $menu_names);
        // If no possible parent menu items were found, there is nothing to display.
        if (empty($parent_element)) {
            drupal_set_message(t('No possible parent menu items found.'), 'warning');
            return;
        }

        $form['menu_parent'] = $parent_element;
        $form['menu_parent']['#title'] = t('Parent item');
        $form['menu_parent']['#attributes']['class'][] = 'menu-parent-select';


        $types = \Drupal::entityTypeManager()
            ->getStorage('node_type')
            ->loadMultiple();

        foreach($types as $type) {
            $content_types[$type->id()] = $type->label();
        }

        $form['option_title'] = [
            '#type' => 'textfield',
            '#title' => 'Option Title',
            '#description' => 'Label to be used for checkbox on node.',
            '#default_value' => $config->get('option_title'),
        ];

        $form['menu_title'] = [
            '#type' => 'textfield',
            '#title' => 'Menu Title',
            '#description' => 'Title to be used for Menu Item.',
            '#default_value' => $config->get('menu_title'),
        ];

        $form['menu_weight'] = [
            '#type' => 'textfield',
            '#title' => 'Menu Weight',
            '#description' => 'Weight to be used for Menu Item.',
            '#default_value' => $config->get('menu_weight'),
        ];

        $form['enabled_content_types'] = [
            '#type' => 'checkboxes',
            '#title' => 'Enabled Content Types',
            '#description' => 'This dynamic menu item will be available on enabled content types',
            '#options' => $content_types,
            '#default_value' => $config->get('enabled_content_types'),
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        parent::submitForm($form, $form_state);

        $this->config('dynamic_menu_item.adminsettings')
            ->set('menu_parent', $form_state->getValue('menu_parent'))
            ->set('option_title', $form_state->getValue('option_title'))
            ->set('menu_title', $form_state->getValue('menu_title'))
            ->set('menu_weight', $form_state->getValue('menu_weight'))
            ->set('enabled_content_types', $form_state->getValue('enabled_content_types'))
            ->save();
    }
}
