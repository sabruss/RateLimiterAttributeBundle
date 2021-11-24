<?php

namespace Sabrus\Bundle\RateLimiterAttributeBundle\Attributes;


#[\Attribute(\Attribute::TARGET_METHOD)]
class RateLimiting
{
    public function __construct(
        public string $configuration
    )
    {
    }
}