<?php
/**
 * @file
 * Contains \Drupal\blocktheme\Form\HandbookSearchForm.
 */

namespace Drupal\blocktheme\Form;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Url;

/**
 * Create form for admin page.
 */
class BlockthemeAdminSettingsForm extends ConfigFormBase {

    public function getFormId() {
    return 'blocktheme_admin_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('blocktheme.settings');
    $form['blocktheme_themes'] = array(
      '#type'          => 'textarea',
      '#default_value' => $config->get('blocktheme_themes'),
      '#title'         => t('Custom Block Templates'),
      '#description'   => t('Enter one value per row in the form: <em>customtemplate|Friendly Name</em>, where "customtemplate" corresponds to a twig file called <em>block--blocktheme--customtemplate.html.twig</em> as well as to the value of an extra variable <em>blocktheme</em> in the block template.'),
      '#wysiwyg'       => FALSE,
    );
    $form['blocktheme_show_custom_block_theme'] = array(
      '#type' => 'checkbox',
      '#default_value' => $config->get('blocktheme_show_custom_block_theme'),
      '#title' => t('Show Custom Block Theme'),
      '#description' => t('Show the custom block theme used for a block in the !block_admin_page.', array('!block_admin_page' => \Drupal::l('block admin page', Url::fromRoute('block.admin_display')))),
    );

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('blocktheme.settings')
      ->set('blocktheme_themes', $form_state->getValue('blocktheme_themes'))
      ->set('blocktheme_show_custom_block_theme', $form_state->getValue('blocktheme_show_custom_block_theme'))
      ->save();

    parent::submitForm($form, $form_state);
    drupal_theme_rebuild();
  }

  protected function getEditableConfigNames() {
    return ['blocktheme.settings'];
  }
}
