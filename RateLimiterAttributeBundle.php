<?php

namespace Sabrus\Bundle\RateLimiterAttributeBundle;

use Sabrus\Bundle\RateLimiterAttributeBundle\DependencyInjection\Compiler\RateLimitingPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class RateLimiterAttributeBundle extends Bundle
{

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RateLimitingPass());
    }
}