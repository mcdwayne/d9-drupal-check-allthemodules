<?php

namespace Drupal\orcid\Plugin\Block;

use Drupal\user\Entity\User;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a block with a simple text.
 *
 * @Block(
 *   id = "ORCID membership block",
 *   admin_label = @Translation("ORCID Membership"),
 * )
 */
class OrcidBlock extends BlockBase implements ContainerFactoryPluginInterface {
    /**
     * The Current User object.
     *
     * @var \Drupal\Core\Session\AccountInterface
     */
    protected $configFactory;
    protected $currentPath;

    public function __construct(array $configuration, $plugin_id, $plugin_definition, $config_factory, $current_path) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->configFactory = $config_factory;
        $this->currentPath = $current_path;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('config.factory'),
            $container->get('path.current')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function build() {
        $current_path = $this->currentPath->getPath();
        $path_parts = explode('/', $current_path);
        $user = User::load($path_parts[2]);
        $config = $this->configFactory->get('orcid.settings');
        $name = $config->get('name_field');
        $identifier = $user->get($name)->value;
        $markup = $identifier ? "<p><a href='https://orcid.org'><img alt='ORCID logo' src='https://orcid.org/sites/default/files/images/orcid_16x16.png' width='16' height='16' hspace='4' /></a> 
                                    <a href='https://orcid.org/{$identifier}'>https://orcid.org/{$identifier}</a></p>" : '';
        return [
            '#markup' => $markup,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function blockAccess(AccountInterface $account) {
        return AccessResult::allowedIfHasPermission($account, 'access content');
    }

    public function getCacheMaxAge() {
        return 0;
    }
}
