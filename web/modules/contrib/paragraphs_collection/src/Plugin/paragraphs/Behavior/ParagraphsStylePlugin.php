<?php

namespace Drupal\paragraphs_collection\Plugin\paragraphs\Behavior;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;
use Drupal\paragraphs_collection\StyleDiscoveryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides style selection plugin.
 *
 * @ParagraphsBehavior(
 *   id = "style",
 *   label = @Translation("Style"),
 *   description = @Translation("Allows the selection of a pre-defined visual style for a whole paragraph."),
 *   weight = 0
 * )
 */
class ParagraphsStylePlugin extends ParagraphsBehaviorBase implements ContainerFactoryPluginInterface {

  use DependencySerializationTrait;

   /**
    * The yaml style discovery.
    *
    * @var \Drupal\paragraphs_collection\StyleDiscovery
    */
   protected $yamlStyleDiscovery;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
   protected $currentUser;

   /**
    * Constructs a new SelectionBase object.
    *
    * @param array $configuration
    *   A configuration array containing information about the plugin instance.
    * @param string $plugin_id
    *   The plugin_id for the plugin instance.
    * @param mixed $plugin_definition
    *   The plugin implementation definition.
    * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
    *   The entity field manager.
    * @param \Drupal\paragraphs_collection\StyleDiscoveryInterface $yaml
    *   The yaml style discovery.
    * @param \Drupal\Core\Session\AccountProxyInterface $current_user
    *   The current user.
    */
   public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManager $entity_field_manager, StyleDiscoveryInterface $yaml, AccountProxyInterface $current_user) {
     parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_field_manager);
     $this->yamlStyleDiscovery = $yaml;
     $this->currentUser = $current_user;
   }

   /**
    * {@inheritdoc}
    */
   public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
     return new static($configuration, $plugin_id, $plugin_definition,
       $container->get('entity_field.manager'),
       $container->get('paragraphs_collection.style_discovery'),
       $container->get('current_user')
     );
   }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'paragraphs_collection/plugin_admin';

    // Create a unique id for the wrapper.
    $wrapper_id = Html::getUniqueId('style-wrapper');
    $form['style_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['paragraphs-plugin-inline-container', 'multiple-lines', 'paragraphs-style-select'],
        'id' => $wrapper_id,
      ],
    ];

    $paragraph_styles = $this->getStyles($paragraph);
    foreach (array_keys($this->configuration['groups']) as $group_id) {
      $group_default = $this->configuration['groups'][$group_id]['default'];
      $default_style = !empty($paragraph_styles[$group_id]) ? $paragraph_styles[$group_id] : $group_default;

      // We filter advanced styles if the user has no permission to use them and
      // disable it in case an advanced style is currently selected and enabled.
      $style_options = $this->getStyleOptions($group_id, $group_default, TRUE);
      $disabled = FALSE;
      if ($default_style && !$paragraph->isNew()) {
        $default_style_definition = $this->yamlStyleDiscovery->getStyle($default_style);
        if ($default_style_definition && !$this->yamlStyleDiscovery->isAllowedAccess($default_style_definition)) {
          $style_options[$default_style] = $default_style_definition['title'];
          $disabled = TRUE;
        }
      }

      // Show the styles selection if:
      // - The default style is disabled on an existing paragraph
      // - There is more than one style option
      // - There is exactly one style option and no style group default style.
      if (($disabled && !$paragraph->isNew()) || count($style_options) > 1 || (count($style_options) === 1 && !$group_default)) {
        $form['style_wrapper']['styles'][$group_id] = [
          '#type' => 'select',
          '#title' => $this->yamlStyleDiscovery->getGroupWidgetLabel($group_id),
          '#options' => $style_options,
          '#default_value' => $default_style,
          '#attributes' => ['class' => ['paragraphs-style']],
          '#disabled' => $disabled,
          '#attributes' => ['class' => ['paragraphs-plugin-form-element']],
        ];

        // Allow empty option in case there is no default style configured.
        if (empty($group_default)) {
          $form['style_wrapper']['styles'][$group_id]['#empty_option'] = $this->t('- Default -');
        }
      }
    }

    // Clean the current plugin form if there are no styles to display.
    if (empty($form['style_wrapper']['styles'])) {
      // @todo: Unset the current plugin form element passed by reference.
      // Remove after https://www.drupal.org/project/paragraphs/issues/2971115.
      $form = [];
      return [];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $paragraph->setBehaviorSettings($this->pluginId, $form_state->getValue('style_wrapper', []));
  }

  /**
   * Gets sorted style titles keyed by their names belonging to the given group.
   *
   * If an empty string is given, returns all styles.
   *
   * @param string $group
   *   (optional) The style group. Defaults to empty string.
   * @param string $default_style_key
   *   (optional) Default style key will be displayed first.
   * @param bool $access_check
   *   (optional) Whether we should check the style access. Defaults to false.
   *
   * @return array
   *   An array of style titles keyed by the respective style machine names.
   */
  protected function getStyleOptions($group = '', $default_style_key = '', $access_check = FALSE) {
    $styles = $this->yamlStyleDiscovery->getStyleOptions($group, $access_check);
    if (isset($styles[$default_style_key])) {
      // Show default selection as a first option in the list with dashes.
      return [$default_style_key => $this->t('- @name -', ['@name' => $styles[$default_style_key]])] + $styles;
    }
    return $styles;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['groups'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Style groups'),
      '#description' => $this->t('Restrict available styles to certain style groups.'),
      '#options' => $this->yamlStyleDiscovery->getStyleGroupsLabel(),
      '#default_value' => array_keys($this->configuration['groups']),
      '#ajax' => [
        'callback' => [$this, 'updateDefaultStyle'],
        'wrapper' => 'style-wrapper',
      ],
    ];
    // @todo: Remove getCompleteFormState() after https://www.drupal.org/project/drupal/issues/2798261.
    $group_key = ['behavior_plugins', $this->getPluginId(), 'settings', 'groups'];
    $groups = $form_state->getCompleteFormState()->getValue($group_key, $this->configuration['groups']);
    $form['groups_defaults'] = [
      '#type' => 'container',
      '#prefix' => '<div id="style-wrapper">',
      '#suffix' => '</div>',
    ];

    foreach (array_keys(array_filter($groups)) as $group_id) {
      $default = '';
      if (!empty($this->configuration['groups'][$group_id]['default'])) {
        $default = $this->configuration['groups'][$group_id]['default'];
      }
      $group_label = $this->yamlStyleDiscovery->getGroupLabel($group_id);
      $form['groups_defaults'][$group_id]['default'] = [
        '#type' => 'select',
        '#title' => $this->t('@label default style', ['@label' => $group_label]),
        '#empty_option' => $this->t('- None -'),
        '#options' => $this->yamlStyleDiscovery->getStyleOptions($group_id),
        '#description' => $this->t('Default option for the @label group on a behavior form.', ['@label' => $group_label]),
        '#default_value' => $default,
      ];
    }

    return $form;
  }

  /**
   * Ajax callback for the style group dropdown.
   */
  public static function updateDefaultStyle(array $form, FormStateInterface $form_state) {
    $group_select = $form_state->getTriggeringElement();
    // Gets the behavior plugin settings form.
    $settings_form = NestedArray::getValue($form, array_slice($group_select['#array_parents'], 0, -2));
    return $settings_form['groups_defaults'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // @todo Selecting group(s) in style plugin https://www.drupal.org/node/2841304
    if (empty($this->yamlStyleDiscovery->getStyleGroups())) {
      $form_state->setErrorByName('message', $this->t('There is no style group available, the style plugin can not be enabled.'));
    }
    if (!array_filter($form_state->getValue('groups'))) {
      $form_state->setErrorByName('groups', $this->t('The style plugin cannot be enabled if no groups are selected.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['groups'] = $form_state->getValue('groups_defaults');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'groups' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    // Get all config for all styles and if it is not set, fallback to the
    // default style. If default style is set to none, no style will be applied.
    $paragraph_styles = $this->getStyles($paragraph);
    foreach ($this->configuration['groups'] as $key => $value) {
      $style = $this->yamlStyleDiscovery->getStyle($paragraph_styles[$key], $this->configuration['groups'][$key]['default']);
      if ($style) {
        $build['#attributes']['class'][] = 'paragraphs-behavior-' . $this->getPluginId() . '--' . $style['name'];
        if (!isset($build['#attached']['library'])) {
          $build['#attached']['library'] = [];
        }
        $build['#attached']['library'] = array_merge($style['libraries'], $build['#attached']['library']);

        // Add CSS classes from style configuration if they are defined.
        if (!empty($style['classes'])) {
          $build['#attributes']['class'] = array_merge($style['classes'], $build['#attributes']['class']);
        }

        // Add attributes defined in the configuration files to the #attributes array.
        if (!empty($style['attributes'])) {
          $build['#attributes'] = array_merge($style['attributes'], $build['#attributes']);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(Paragraph $paragraph) {
    $style_options = $this->yamlStyleDiscovery->getStyleOptions();
    $summary = [];
    if ($styles = $paragraph->getBehaviorSetting($this->getPluginId(), 'styles')) {
      foreach ($styles as $group_id => $style) {
        // Check if the style set in the Paragraph is enabled in the collection.
        if (isset($style_options[$style]) && (!isset($this->configuration['groups'][$group_id]) || $style != $this->configuration['groups'][$group_id]['default'])) {
          $summary[] = [
            'label' => $this->yamlStyleDiscovery->getGroupWidgetLabel($group_id),
            'value' => $style_options[$style]
          ];
        }
      }
    }
    return $summary;
  }

  /**
   * Ajax callback for loading the style description for the currently
   * selected style.
   *
   * @param array $form
   *   A nested array of form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form element representing the style description.
   */
  public static function ajaxStyleSelect(array $form, FormStateInterface $form_state) {
    // Gets the style description accordingly.
    $select = $form_state->getTriggeringElement();
    $style_discovery = \Drupal::getContainer()->get('paragraphs_collection.style_discovery');
    $styles = $style_discovery->getStyles();
    $description = '';
    if (isset($styles[$select['#value']]['description'])) {
      $description = $styles[$select['#value']]['description'];
    }

    // Gets the complete behavior plugin form.
    $return_form = NestedArray::getValue($form, array_slice($select['#array_parents'], 0, -2));
    $return_form['style_wrapper']['style_description']['#markup'] = $description;
    return $return_form;
  }

  /**
   * Get the template setting of the styles of the Paragraph.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph.
   *
   * @return string[]
   *   The template setting of the selected styles.
   */
  public function getStyleTemplates(ParagraphInterface $paragraph) {
    if ($paragraph->getParagraphType()->hasEnabledBehaviorPlugin('style')) {
      $templates = [];
      $paragraph_styles = $this->getStyles($paragraph);
      foreach ($paragraph_styles as $group_name => $paragraph_style) {
        if ($style = $this->yamlStyleDiscovery->getStyle($paragraph_style)) {
          if (!empty($style['template'])) {
            $templates[] = $style['template'];
          }
        }
      }
      return $templates;
    }
    return NULL;
  }

  /**
   * Gets the current styles for each enabled group.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph.
   *
   * @return string[]
   *   A list of enabled styles, keyed by the group ID.
   */
  public function getStyles(ParagraphInterface $paragraph) {
    $paragraph_styles = [];
    foreach ($this->configuration['groups'] as $group_id => $group_configuration) {
      $paragraph_styles[$group_id] = $group_configuration['default'];
    }
    if ($styles_config = $paragraph->getBehaviorSetting('style', 'styles')) {
      // Loop over all groups to get the behavior setting.
      foreach ($styles_config as $group_id => $style) {
        $paragraph_styles[$group_id] = $style;
      }
    }
    elseif ($style_config = $paragraph->getBehaviorSetting('style', 'style')) {
      // If there is old config, map it to the current one.
      if ($style = $this->yamlStyleDiscovery->getStyle($style_config)) {
        foreach (array_keys($this->configuration['groups']) as $group_id) {
          if (in_array($group_id, $style['groups'])) {
            $paragraph_styles[$group_id] = $paragraph->getBehaviorSetting($this->getPluginId(), 'style');
          }
        }
      }
    }

    return $paragraph_styles;
  }

}
