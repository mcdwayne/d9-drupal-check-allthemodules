<?php
namespace Drupal\stop_broken_link_in_body\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class StopBrokenLinkForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
	return 'stop_broken_link_form';
  }
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['stop_broken_link.settings'];
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state ) {
  	$config = $this->config('stop_broken_link.settings');
  	// Get List Of Content Type.
  	$node_type = node_type_get_names();
	  $form['stop_broken_link_in_body_node_types'] = [
		  '#type' => 'checkboxes',
		  '#options' => $node_type,
		  '#title' => $this->t('List of content type in which you want stop broken link'),
		  '#description' => $this->t('Select Content type which you want to stop broken link in body.'),
		  '#default_value' => is_array($config->get('stop_broken_link_in_body_node_types')) ? array_filter($config->get('stop_broken_link_in_body_node_types')) : [NULL],
	 ];
	 $form['stop_broken_link_in_body_restrict_the_number'] = [
	    '#type' => 'number',
		  '#title' => $this->t("Configuration to restrict the number of links to validate"),
		  '#description' => $this->t('Add the value to restrict the number of links to validate.'),
		  '#default_value' => !(is_null($config->get('stop_broken_link_in_body_restrict_the_number'))) ? $config->get('stop_broken_link_in_body_restrict_the_number') : 30,
      '#min' => 1,
      '#max' => 2500,
	 ];
	return parent::buildForm($form, $form_state);
  }
   /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  	// Retrieve the configuration
    $this->config('stop_broken_link.settings')
      ->set('stop_broken_link_in_body_node_types', $form_state->getValue('stop_broken_link_in_body_node_types'))
      ->set('stop_broken_link_in_body_restrict_the_number', $form_state->getValue('stop_broken_link_in_body_restrict_the_number'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}

?>
