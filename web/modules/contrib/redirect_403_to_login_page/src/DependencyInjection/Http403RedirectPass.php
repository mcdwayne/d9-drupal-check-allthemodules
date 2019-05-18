<?php
/**
 * Created by PhpStorm.
 * User: evolve
 * Date: 3/8/18
 * Time: 1:56 PM
 */

namespace Drupal\redirect_403_to_login_page\DependencyInjection;

use Drupal\redirect_403_to_login_page\EventSubscriber\Redirect403ToLoginExceptionHtmlSubscriber;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Http403RedirectPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('exception.default_html');
        $definition->setClass(Redirect403ToLoginExceptionHtmlSubscriber::class);
    }
}