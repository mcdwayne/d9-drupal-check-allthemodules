<?php

namespace Drupal\ultimenu\Plugin\Block;

use Drupal\Core\Url;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ultimenu\UltimenuManagerInterface;
use Drupal\ultimenu\UltimenuSkinInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'Ultimenu' block.
 *
 * @Block(
 *  id = "ultimenu_block",
 *  admin_label = @Translation("Ultimenu block"),
 *  category = @Translation("Ultimenu"),
 *  deriver = "Drupal\ultimenu\Plugin\Derivative\UltimenuBlock",
 * )
 */
class UltimenuBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * The Ultimenu manager service.
   *
   * @var \Drupal\ultimenu\UltimenuManagerInterface
   */
  protected $ultimenuManager;

  /**
   * The Ultimenu skin service.
   *
   * @var \Drupal\ultimenu\UltimenuSkinInterface
   */
  protected $ultimenuSkin;

  /**
   * Constructs an UltimenuBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The ultimenu manager.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   The ultimenu manager.
   * @param \Drupal\ultimenu\UltimenuManagerInterface $ultimenu_manager
   *   The ultimenu manager.
   * @param \Drupal\ultimenu\UltimenuSkinInterface $ultimenu_skin
   *   The ultimenu skin service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user, LibraryDiscoveryInterface $library_discovery, UltimenuManagerInterface $ultimenu_manager, UltimenuSkinInterface $ultimenu_skin) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->libraryDiscovery = $library_discovery;
    $this->ultimenuManager = $ultimenu_manager;
    $this->ultimenuSkin = $ultimenu_skin;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('library.discovery'),
      $container->get('ultimenu.manager'),
      $container->get('ultimenu.skin')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function delta() {
    // Derivatives are prefixed with 'ultimenu-', e.g.: ultimenu-main.
    $id = $this->getDerivativeId();
    return substr($id, 9);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'ajaxify' => FALSE,
      'regions' => [],
      'skin' => 'module|ultimenu--dark',
      'submenu' => FALSE,
      'orientation' => 'ultimenu--htb',
      'submenu_position' => '',
      'canvas_off' => '#header',
      'canvas_on' => '#main-wrapper, .featured-top, .site-footer',
      'canvas_skin' => 'scalein',
    ];
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, FormStateInterface $form_state) {
    if ($this->currentUser->hasPermission('administer ultimenu')) {
      $ultimenu_admin = Url::fromRoute('ultimenu.settings')->toString();
      $form['ajaxify'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Ajaxify'),
        '#default_value' => isset($this->configuration['ajaxify']) ? $this->configuration['ajaxify'] : FALSE,
        '#description'   => $this->t('Check to load ultimenu region contents using AJAX. Only makes sense for massive contents.'),
      ];

      // @todo all: $regions = (array) $this->ultimenuManager->getSetting('regions');
      $regions = $this->ultimenuManager->getRegionsByMenu($this->delta());
      $states['visible'][':input[name="settings[ajaxify]"]'] = ['checked' => TRUE];
      $form['regions'] = [
        '#type'          => 'checkboxes',
        '#title'         => $this->t('Ajaxifed regions'),
        '#options'       => $regions,
        '#default_value' => isset($this->configuration['regions']) ? array_values((array) $this->configuration['regions']) : [],
        '#description'   => $this->t('Check which regions should be ajaxified, leaving those unchecked as non-ajaxed regions. Be sure to enable the regions at <a href=":url">Ultimenu admin</a>.', [':url' => $ultimenu_admin]),
        '#states'        => $states,
      ];

      $form['skin'] = [
        '#type'          => 'select',
        '#title'         => $this->t('Ultimenu skin'),
        '#default_value' => $this->configuration['skin'],
        '#options'       => $this->ultimenuSkin->loadMultiple(),
        '#empty_option'  => $this->t('- None -'),
        '#description'   => $this->t('Choose the skin for this block. You can supply custom skins at <a href=":ultimenu_settings" target="_blank">Ultimenu settings</a>. The skin can be made specific to this block using the proper class by each menu name. Be sure to <a href=":clear" target="_blank">clear the cache</a> if trouble to see the new skin applied.', [':ultimenu_settings' => $ultimenu_admin, ':clear' => Url::fromRoute('system.performance_settings')->toString()]),
      ];

      $form['orientation'] = [
        '#type'           => 'select',
        '#title'          => $this->t('Flyout orientation'),
        '#default_value'  => $this->configuration['orientation'],
        '#options'        => [
          'ultimenu--htb' => $this->t('Horizontal to bottom'),
          'ultimenu--htt' => $this->t('Horizontal to top'),
          'ultimenu--vtl' => $this->t('Vertical to left'),
          'ultimenu--vtr' => $this->t('Vertical to right'),
        ],
        '#description'   => $this->t('Choose the orientation of the flyout, depending on the placement. At sidebar left, <strong>Vertical to right</strong>. At header, <strong>Horizontal to bottom</strong>. At footer, <strong>Horizontal to top</strong>'),
      ];

      $form['submenu'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Render submenu'),
        '#default_value' => $this->configuration['submenu'],
        '#description'   => $this->t('Render the relevant submenus inside the Ultimenu region without using Block admin, and independent from blocks. Alternatively use core Menu level option with regular menu block when core supports the "Fixed parent item", see <a href=":url" target="_blank">#2631468</a>. <br /><strong>Important!</strong> Be sure to check "<strong>Show as expanded</strong>" at the parent menu item edit page as needed, otherwise no submenus will be rendered.', [':url' => 'https://www.drupal.org/node/2631468']),
      ];

      $form['submenu_position'] = [
        '#type'          => 'select',
        '#title'         => $this->t('Submenu position'),
        '#options'       => [
          'bottom' => $this->t('Bottom'),
          'top'    => $this->t('Top'),
        ],
        '#empty_option'  => $this->t('- None -'),
        '#default_value' => $this->configuration['submenu_position'],
        '#description'   => $this->t('Choose where to place the submenu, either before or after existing blocks. Default to Top.'),
      ];

      if ($this->configuration['id'] == 'ultimenu_block:ultimenu-main') {
        $form['canvas_off'] = [
          '#type'          => 'textfield',
          '#title'         => $this->t('Off-canvas element'),
          '#default_value' => $this->configuration['canvas_off'],
          '#description'   => $this->t('Valid CSS selector for the off-canvas element. Only one can exist, for Bartik, e.g.: <code>#header</code> or <code>.region-primary-menu</code> (not good, just works). But not both.'),
        ];

        $form['canvas_on'] = [
          '#type'          => 'textfield',
          '#title'         => $this->t('On-canvas element'),
          '#default_value' => $this->configuration['canvas_on'],
          '#description'   => $this->t('Valid CSS selector for the on-canvas element. Can be multiple, for Bartik, e.g.: <code>#main-wrapper, .highlighted, .featured-top, .site-footer</code> <br>Visit <b>/admin/help/ultimenu</b> under <b>STYLING</b> section for details.'),
        ];

        $skins = $this->ultimenuSkin->getOffCanvasSkins();
        $form['canvas_skin'] = [
          '#type'          => 'select',
          '#title'         => $this->t('Off-canvas skin'),
          '#options'       => array_combine($skins, $skins),
          '#default_value' => $this->configuration['canvas_skin'],
          '#description'   => $this->t('The off-canvas skin. Note the name oldies is meant for old browsers up, but not as smoother. Consider Modernizr.js to support old browsers with advanced transform effects. More custom works are required as usual.'),
        ];
      }
    }

    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    foreach (array_keys($this->defaultConfiguration()) as $key) {
      $value = $form_state->getValue($key);
      $this->configuration[$key] = $key == 'regions' ? array_filter($value) : $value;
    }

    // Invalidate the library discovery cache to update the new skin discovery.
    $this->ultimenuSkin->clearCachedDefinitions();
    $this->libraryDiscovery->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $menu_name     = $this->delta();
    $skin          = $this->configuration['skin'];
    $provider      = '';
    $skin_name     = '';
    $skin_basename = '';

    // Load the specified block skin.
    if (!empty($skin)) {
      $skin_css_path = $this->ultimenuSkin->getPath($skin);
      $skin_basename = $this->ultimenuSkin->getName($skin_css_path);

      // Fetch the skin file name from the setting.
      list($provider, $skin_name) = array_pad(array_map('trim', explode("|", $skin, 2)), 2, NULL);
    }

    // Provide the settings for further process.
    $build['config'] = [
      'bid'              => $this->getDerivativeId(),
      'menu_name'        => $menu_name,
      'regions'          => empty($this->configuration['regions']) ? [] : array_filter($this->configuration['regions']),
      'skin_name'        => $skin_name,
      'skin_provider'    => $provider,
      'skin_basename'    => $skin_basename,
    ] + $this->configuration;

    return $this->ultimenuManager->build($build);
  }

}
