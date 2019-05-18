<?php


namespace Drupal\layouts\Plugin\Layout;


use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\image\Entity\ImageStyle;

class NavigationLayout extends DefaultConfigLayout implements PluginFormInterface {

  public function build(array $regions) {
    $build = parent::build($regions);

    $logo = \Drupal::entityTypeManager()->getStorage('media')->load($this->configuration['logo']);
    $src = ImageStyle::load($this->configuration['style'])->buildUrl($logo->field_media_image->first()->entity->getFileUri());
    $build['logo'][] = [
      '#markup' => "<img src=\"$src\" />"
    ];
    $menu_name = $this->configuration['menu'];
    /** @var \Drupal\Core\Menu\MenuLinkTreeInterface $menu_tree */
    $menu_tree = \Drupal::service("menu.link_tree");
    $parameters = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);
    $parameters->setMinDepth(1);

    $tree = $menu_tree->load($menu_name, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $menu_tree->transform($tree, $manipulators);
    $build['menu'][] = $menu_tree->build($tree);
    return $build;
  }


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    $configuration['logo'] = '';
    $configuration['menu'] = '';
    return $configuration;
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['logo'] = [
      '#title' => $this->t('Logo Media'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'media',
      '#selection_handler' => 'default',
      '#required' => TRUE,
      '#selection_settings' => [
        'target_bundles' => ['image'],
      ],
    ];
    if (!empty($this->configuration['logo'])) {
      $form['logo']['#default_value'] = \Drupal::entityTypeManager()->getStorage('media')->load($this->configuration['logo']);
    }
    $form['style'] = [
      '#title' => $this->t('Logo Image Style'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'image_style',
      '#selection_handler' => 'default',
      '#required' => TRUE,
    ];
    if (!empty($this->configuration['style'])) {
      $form['style']['#default_value'] = \Drupal::entityTypeManager()->getStorage('image_style')->load($this->configuration['style']);
    }
    $form['menu'] = [
      '#title' => $this->t('Menu'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'menu',
      '#selection_handler' => 'default',
      '#required' => TRUE,
    ];
    if (!empty($this->configuration['menu'])) {
      $form['menu']['#default_value'] = \Drupal::entityTypeManager()->getStorage('menu')->load($this->configuration['menu']);
    }
    return $form;
  }

  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['logo'] = $form_state->getValue('logo');
    $this->configuration['style'] = $form_state->getValue('style');
    $this->configuration['menu'] = $form_state->getValue('menu');
  }

}
