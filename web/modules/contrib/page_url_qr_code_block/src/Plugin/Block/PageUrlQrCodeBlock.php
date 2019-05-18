<?php

/**
 * @file
 * Contains \Drupal\page_url_qr_code_block\Plugin\Block\page_url_qr_code_block.
 */

namespace Drupal\page_url_qr_code_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides my custom block.
 *
 * @Block(
 *   id = "page_url_qr_code_block",
 *   admin_label = @Translation("Page URL QR Code"),
 *   category = @Translation("Blocks")
 * )
 */
class PageUrlQrCodeBlock extends BlockBase implements BlockPluginInterface {

    /**
     * Constructs a new BookNavigationBlock instance.
     *
     * @param array $configuration
     *   A configuration array containing information about the plugin instance.
     * @param string $plugin_id
     *   The plugin_id for the plugin instance.
     * @param mixed $plugin_definition
     *   The plugin implementation definition.
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
    }

    /**
     * {@inheritdoc}
     */
    public function defaultConfiguration() {

        $default_config = \Drupal::config('page_url_qr_code_block.settings');
        return array(
            'page_url_qr_code_caption' => $default_config->get('page_url_qr_code_block.page_url_qr_code_caption'),
            'page_url_qr_code_alt' => $default_config->get('page_url_qr_code_block.page_url_qr_code_alt'),
            'page_url_qr_code_width_height' => $default_config->get('page_url_qr_code_block.page_url_qr_code_width_height'),
            'page_url_qr_code_api' => $default_config->get('page_url_qr_code_block.page_url_qr_code_api'),
        );
    }

    /**
     * {@inheritdoc}
     */
    function blockForm($form, FormStateInterface $form_state) {
        $form = parent::blockForm($form, $form_state);

        $config = $this->getConfiguration();

        $form['page_url_qr_code_caption'] = array(
            '#type' => 'textfield',
            '#title' => t('Caption'),
            '#description' => t('Will display under the QR Code'),
            '#default_value' => isset($config['page_url_qr_code_caption']) ? $config['page_url_qr_code_caption'] : 'Page URL'
        );
        $form['page_url_qr_code_alt'] = array(
            '#type' => 'textfield',
            '#title' => t('Alt Text'),
            '#default_value' => isset($config['page_url_qr_code_alt']) ? $config['page_url_qr_code_alt'] : 'QR code for this page URL'
        );
        $form['page_url_qr_code_width_height'] = array(
            '#type' => 'textfield',
            '#title' => t('QR Code Width & Height'),
            '#description' => t('Width & Height will be same. i.e. 150'),
            '#default_value' => isset($config['page_url_qr_code_width_height']) ? $config['page_url_qr_code_width_height'] : '150'
        );
        $form['page_url_qr_code_api'] = array(
            '#type' => 'radios',
            '#title' => t('Select API'),
            '#description' => t('Select other API where Google is restricted'),
            '#options' => array('google'=> 'Google API (recommended)','liantu'=> 'Liantu API'),
            '#default_value' => isset($config['page_url_qr_code_api']) ? $config['page_url_qr_code_api'] : 'google'
        );
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function blockSubmit($form, FormStateInterface $form_state) {
        $this->configuration['page_url_qr_code_caption'] = $form_state->getValue('page_url_qr_code_caption');
        $this->configuration['page_url_qr_code_alt'] = $form_state->getValue('page_url_qr_code_alt');
        $this->configuration['page_url_qr_code_width_height'] = $form_state->getValue('page_url_qr_code_width_height');
        $this->configuration['page_url_qr_code_api'] = $form_state->getValue('page_url_qr_code_api');
    }

    /**
     * {@inheritdoc}
     */
    public function build() {

        global $base_url;

        $path = \Drupal::request()->getRequestUri();
        $path = \Drupal::service('path.alias_manager')->getAliasByPath($path);
        
        $width = $this->configuration['page_url_qr_code_width_height'];
        $api = $this->configuration['page_url_qr_code_api'];

        $page_url = urlencode($base_url . $path);
        
        if ('liantu' == $api) {
            $url = "http://qr.liantu.com/api.php?bg=ffffff&w={$width}&text={$page_url}";
        } else {
            $url = "http://chart.apis.google.com/chart?chs={$width}x{$width}&cht=qr&chl={$page_url}";
        }
        
        return array(
            '#theme' => 'page_url_qr_code_block',
            '#url' => $url,
            '#alt' => $this->configuration['page_url_qr_code_alt'],
            '#width' => $width,
            '#height' => $this->configuration['page_url_qr_code_width_height'],
            '#caption' => $this->configuration['page_url_qr_code_caption'],
            '#attached' => array(
                'library' => array('page_url_qr_code_block/page_url_qr_code_block_style'),
            ),
            '#cache' => [
                'contexts' => ['url'],
                'max-age' => 0,
            ],
        );
    }

}

?>