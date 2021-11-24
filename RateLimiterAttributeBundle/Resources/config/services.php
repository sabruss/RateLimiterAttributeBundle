<?php

namespace Sabrus\Bundle\RateLimiterAttributeBundle\Loader\Configurator;

use Sabrus\Bundle\RateLimiterAttributeBundle\EventListener\ApplyRateLimitingListener;
use Sabrus\Bundle\RateLimiterAttributeBundle\EventListener\RateLimitingResponseHeadersListener;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set(ApplyRateLimitingListener::class)
        ->args([
            service('security.token_storage'),
            service('request_stack'),
            service('security.role_hierarchy')
        ])
        ->tag('kernel.event_subscriber')
        ->set(RateLimitingResponseHeadersListener::class)
        ->tag('kernel.event_subscriber');
};