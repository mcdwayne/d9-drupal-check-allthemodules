<?php

namespace Drupal\redirect_403_to_login_page;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\redirect_403_to_login_page\DependencyInjection\Http403RedirectPass;

class Redirect403ToLoginServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $container)
    {
        $container->addCompilerPass(new Http403RedirectPass());
    }

}