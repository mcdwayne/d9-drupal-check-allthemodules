<?php

namespace Drupal\fblikebutton\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Provides a Facebook Like Button Block
 *
 * @Block(
 *   id = "fblikebutton_block",
 *   admin_label = @Translation("Facebook Like Button"),
 * )
 */

class FblikebuttonBlock extends BlockBase {

  /**
  * {@inheritdoc}
  */
  public function build() {

    $block = array(
      '#theme' => 'fblikebutton',
      '#layout' => $this->configuration['layout'],
      '#show_faces' => $this->configuration['show_faces'],
      '#action' => $this->configuration['action'],
      '#font' => $this->configuration['font'],
      '#color_scheme' => $this->configuration['color_scheme'],
      '#language' => $this->configuration['language'],
    );

    // If it's not for the current page
    if($this->configuration['block_url'] != '<current>') {
      $block['#url'] = $this->configuration['block_url'];
    } else {
      
      // Avoid this block to be cached
      $block['#cache'] = array(
        'max-age' => 0,
      );
      
      /**
       * Drupal uses the /node path to refers to the frontpage. That's why facebook
       * could point to www.example.com/node instead of wwww.example.com.
       * 
       * To avoid this, we check if the current path is the frontpage
       */
      
      // Check if the path is pointing home
      if(\Drupal::routeMatch()->getRouteName() == 'view.frontpage.page_1') {
        global $base_url;
        $block['#url'] = $base_url;
      } else {
        $block['#url'] = Url::fromRoute('<current>', array(), array('absolute' => true))->toString();
      }
    }

    return $block;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    global $base_url;
    return array(
      'block_url' => $base_url,
      'layout' => 'standard',
      'show_faces' => TRUE,
      'action' => 'like',
      'font' => 'arial',
      'color_scheme' => 'light',
      'language' => 'en_US',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state ) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Button settings'),
      '#open' => TRUE,
    );
    $form['settings']['block_url'] = array(
      '#type' => 'textfield',
      '#default_value' => $config['block_url'],
      '#description' => $this->t('URL of the page to like (could be your homepage or a facebook page e.g.)<br> You can also specify &lt;current&gt; to establish the url for the current viewed page in your site'),
    );
    $form['appearance'] = array(
      '#type' => 'details',
      '#title' => $this->t('Button appearance'),
      '#open' => FALSE,
    );
    $form['appearance']['layout'] = array(
      '#type' => 'select',
      '#title' => $this->t('Layout style'),
      '#options' => array('standard' => $this->t('Standard'),
                          'box_count' => $this->t('Box Count'),
                          'button_count' => $this->t('Button Count'),
                          'button' => $this->t('Button')),
      '#default_value' => $config['layout'],
      '#description' => $this->t('Determines the size and amount of social context next to the button'),
    );
    // The actial values passed in from the options will be converted to a boolean
    // in the validation function, so it doesn't really matter what we use.
    $form['appearance']['show_faces'] = array(
      '#type' => 'select',
      '#title' => $this->t('Display faces in the box'),
      '#options' => array(TRUE => $this->t('Show faces'), FALSE => $this->t('Do not show faces')),
      '#default_value' => $config['show_faces'],
      '#description' => $this->t('Show profile pictures below the button. Only works with Standard layout'),
    );
    $form['appearance']['action'] = array(
      '#type' => 'select',
      '#title' => $this->t('Verb to display'),
      '#options' => array('like' => $this->t('Like'), 'recommend' => $this->t('Recommend')),
      '#default_value' => $config['action'],
      '#description' => $this->t('The verb to display in the button.'),
    );
    $form['appearance']['font'] = array(
      '#type' => 'select',
      '#title' => $this->t('Font'),
      '#options' => array(
        'arial' => 'Arial',
        'lucida+grande' => 'Lucida Grande',
        'segoe+ui' => 'Segoe UI',
        'tahoma' => 'Tahoma',
        'trebuchet+ms' => 'Trebuchet MS',
        'verdana' => 'Verdana'
      ),
      '#default_value' => $config['font'],
      '#description' => $this->t('The font to display in the button'),
    );
    $form['appearance']['color_scheme'] = array(
      '#type' => 'select',
      '#title' => $this->t('Color scheme'),
      '#options' => array('light' => $this->t('Light'), 'dark' => $this->t('Dark')),
      '#default_value' => $config['color_scheme'],
      '#description' => $this->t('The color scheme of box environtment'),
    );
    $form['appearance']['language'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Language'),
      '#default_value' => $config['language'],
      '#description' => $this->t('Specific language to use. Default is English. Examples:<br />French (France): <em>fr_FR</em><br />French (Canada): <em>fr_CA</em><br />More information can be found at http://developers.facebook.com/docs/internationalization/ and a full XML list can be found at http://www.facebook.com/translations/FacebookLocales.xml'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $block_url = $values['settings']['block_url'];
    $layout = $values['appearance']['layout'];
    $show_faces = $values['appearance']['show_faces'];
    $action = $values['appearance']['action'];
    $font = $values['appearance']['font'];
    $color_scheme = $values['appearance']['color_scheme'];
    $language = $values['appearance']['language'];

    $this->configuration['block_url'] = $block_url;
    $this->configuration['layout'] = $layout;
    $this->configuration['show_faces'] = $show_faces;
    $this->configuration['block_url'] = $block_url;
    $this->configuration['action'] = $action;
    $this->configuration['font'] = $font;
    $this->configuration['color_scheme'] = $color_scheme;
    $this->configuration['language'] = $language;
  }
}
