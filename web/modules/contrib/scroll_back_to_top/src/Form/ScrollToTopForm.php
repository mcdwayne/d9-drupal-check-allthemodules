<?php
//dese file bevat alle info om de form op te bouwen van de instellingen

/**
* @file
* Contains \Durpal\scroll_to_top\Form\ScrollToTopForm
*/
    
namespace Drupal\scroll_to_top\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class ScrollToTopForm extends ConfigFormBase{
    /**
    * {@inheridoc}
    */
    public function getFormId(){
        return 'scroll_to_top_form';
    }
    
    /**
    * {@inheridoc}
    */
    public function getEditableConfigNames(){
        
    }
    
    //functie om de Form op te bouwen: grotendeels hetzelfde al Drupal 7 met uitzondering van enkele syntax verschillen
    public function buildForm(array $form, FormStateInterface $form_state){
        //ophalen huidige config
        $config = $this->config('scroll_to_top.settings');
        
        //elk formelement declareren en standaard waarde invullen met de waarde uit de config
        $form['scroll_to_top_label'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
        '#description' => t('label displayed in scroll to top link, default "Back to top".'),
        '#default_value' => $config->get('scroll_to_top_label'),
        '#size' => 10,
        );

        $form['scroll_to_top_position'] = array(
        '#title' => $this->t('Position'),
        '#description' => t('Sroll to top button position'),
        '#type' => 'select',
        '#options' => array(
          1 => t('right'),
          2 => t('left'),
          3 => t('middle'),
        ),
        '#default_value' => $config->get('scroll_to_top_position'),
        );
        $form['scroll_to_top_bg_color_hover'] = array(
        '#type' => 'color',
        '#title' => $this->t('Background color on mouse over.'),
        '#description' => t('Button background color on mouse over default #777777'),
        '#default_value' => $config->get('scroll_to_top_bg_color_hover'),
        '#size' => 10,
        '#maxlength' => 7,
        );
        $form['scroll_to_top_bg_color_out'] = array(
        '#type' => 'color',
        '#title' => $this->t('Background color on mouse out.'),
        '#description' => t('Button background color on mouse over default #CCCCCC'),
        '#default_value' => $config->get('scroll_to_top_bg_color_out'),
        '#size' => 10,
        '#maxlength' => 7,
        );
        $form['scroll_to_top_display_text'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Display label'),
        '#description' => t('Display "BACK TO TOP" text under the button'),
        '#default_value' => $config->get('scroll_to_top_display_text'),
        );
        $form['scroll_to_top_enable_admin_theme'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Enable on administration theme.'),
        '#description' => t('Enable scroll to top button on administartion theme.'),
        '#default_value' => $config->get('scroll_to_top_enable_admin_theme'),
        );
        $form['scroll_to_top_preview'] = array(
        '#type' => 'item',
        '#title' => $this->t('Preview'),
        '#markup' => '<div id="scroll-to-top-prev-container">' . t('Change a setting value to see a preview. "Position" and "enable on admin theme" not included.') . '</div>',
        );
        return parent::buildForm($form, $form_state);
    }
    
    /**
    * {@inheridoc}
    */
    //functie om de form op te halen 
    public function submitForm(array &$form, FormStateInterface $form_state){
        parent::submitForm($form, $form_state);
        
        //ophalen ingevulde zaken
        $label = $form_state -> getValue('scroll_to_top_label');
        $position = $form_state -> getValue('scroll_to_top_position');
        $bg_color = $form_state -> getValue('scroll_to_top_bg_color_out');
        $bg_color_hover = $form_state -> getValue('scroll_to_top_bg_color_hover');
        $display_text = $form_state -> getValue('scroll_to_top_display_text');
        $enable_admin_theme = $form_state -> getValue('scroll_to_top_enable_admin_theme');
        $preview = $form_state -> getValue('scroll_to_top_preview');
        
        //huidige configuratie ophalen zodat deze editeerbaar is
        $config = $this->configFactory()->getEditable('scroll_to_top.settings');
        
        //ingevulde zaken opslaan naar de config
        $config->set('scroll_to_top_label',$label)
            ->set('scroll_to_top_position',$position)
            ->set('scroll_to_top_bg_color_out',$bg_color)
            ->set('scroll_to_top_bg_color_hover',$bg_color_hover)
            ->set('scroll_to_top_display_text',$display_text)
            ->set('scroll_to_top_enable_admin_theme',$enable_admin_theme)
            ->set('scroll_to_top_preview',$preview)
            ->save();
    }
    
}