<?php

namespace Sabrus\Bundle\RateLimiterAttributeBundle\DependencyInjection\Compiler;

use Sabrus\Bundle\RateLimiterAttributeBundle\Attributes\RateLimiting;
use Sabrus\Bundle\RateLimiterAttributeBundle\EventListener\ApplyRateLimitingListener;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class RateLimitingPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(ApplyRateLimitingListener::class)) {
            throw new \LogicException(sprintf('Can not configure non-existent service %s', ApplyRateLimitingListener::class));
        }
        $taggedServices = $container->findTaggedServiceIds('controller.service_arguments');
        /** @var Definition[] $serviceDefinitions */
        $serviceDefinitions = array_map(fn (string $id) => $container->getDefinition($id), array_keys($taggedServices));
        $rateLimiterClassMap = [];

        foreach ($serviceDefinitions as $serviceDefinition) {
            $controllerClass = $serviceDefinition->getClass();
            $reflClass = $container->getReflectionClass($controllerClass);

            foreach ($reflClass->getMethods(\ReflectionMethod::IS_PUBLIC | ~\ReflectionMethod::IS_STATIC) as $reflMethod) {
                $attributes = $reflMethod->getAttributes(RateLimiting::class);
                if (\count($attributes) > 0) {
                    [$attribute] = $attributes;

                    $serviceKey = sprintf('limiter.%s', $attribute->newInstance()->configuration);
                    if (!$container->hasDefinition($serviceKey)) {
                        throw new \RuntimeException(sprintf('Service %s not found', $serviceKey));
          }

                    $classMapKey = sprintf('%s::%s', $serviceDefinition->getClass(), $reflMethod->getName());
                    $rateLimiterClassMap[$classMapKey] = $container->getDefinition($serviceKey);
                }
            }
        }
        $container->getDefinition(ApplyRateLimitingListener::class)->setArgument('$rateLimiterClassMap', $rateLimiterClassMap);
    }
}