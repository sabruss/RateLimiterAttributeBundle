<?php

namespace Sabrus\Bundle\RateLimiterAttributeBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimit;

final class RateLimitingResponseHeadersListener implements EventSubscriberInterface
{
    public function onKernelResponse(ResponseEvent $event): void
    {
        /** @var RateLimit $rateLimit */
        if (($rateLimit = $event->getRequest()->attributes->get('rate_limit')) instanceof RateLimit) {
            $event->getResponse()->headers->add([
                'RateLimit-Remaining' => $rateLimit->getRemainingTokens(),
                'RateLimit-Reset' => time() - $rateLimit->getRetryAfter()->getTimestamp(),
                'RateLimit-Limit' => $rateLimit->getLimit(),
            ]);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => ['onKernelResponse', 1024]];
    }
}