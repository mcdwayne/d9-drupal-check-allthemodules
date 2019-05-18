<?PHP

namespace Drupal\cyr2lat\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Cyr2Lat configuration form.
 */
class Cyr2LatConfigForm extends ConfigFormBase {

  /**
   * Language Manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, LanguageManagerInterface $language_manager) {
    parent::__construct($config_factory);
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cyr2lat_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cyr2lat.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cyr2lat.settings');
    $languages = $this->languageManager->getLanguages();

    $options = [];

    // Make a list of enabled language options for form fields.
    foreach ($languages as $language) {
      $options[$language->getId()] = $language->getName();
    }

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Cyr2Lat transliteration'),
      '#default_value' => $config->get('enabled'),
    ];

    $form['languages'] = [
      '#type' => 'details',
      '#title' => $this->t('Languages'),
      '#open' => TRUE,
    ];
    $form['languages']['cyrillic_language'] = [
      '#type' => 'select',
      '#title' => $this->t('Select cyrillic language'),
      '#description' => $this->t('Choose cyrillic language that will be used as a source for transliteration.'),
      '#options' => $options,
      '#empty_value' => '',
      '#empty_option' => $this->t('Select'),
      '#default_value' => $config->get('cyrillic_language'),
      '#required' => TRUE,
    ];

    $form['languages']['latin_language'] = [
      '#type' => 'select',
      '#title' => $this->t('Select latin language'),
      '#description' => $this->t('Choose destination language for transliteration.'),
      '#options' => $options,
      '#empty_value' => '',
      '#empty_option' => $this->t('Select'),
      '#default_value' => $config->get('latin_language'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('cyr2lat.settings')
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('cyrillic_language', $form_state->getValue('cyrillic_language'))
      ->set('latin_language', $form_state->getValue('latin_language'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}