<?php

namespace Sabrus\Bundle\RateLimiterAttributeBundle\EventListener;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\Exception\RateLimitExceededException;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

final class ApplyRateLimitingListener implements EventSubscriberInterface
{

    public const IGNORED_ROLES = [
        'ROLE_SUPER_ADMIN',
    ];


    public function __construct(
        private TokenStorageInterface  $tokenStorage,
        private RequestStack           $requestStack,
        private RoleHierarchyInterface $roleHierarchy,
        /** @var RateLimiterFactory[] */
        private array                  $rateLimiterClassMap,
    )
    {
    }

    public function onKernelController(KernelEvent $event): void
    {
        if (!class_exists(RateLimit::class) || !$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        /** @var string $controllerClass */
        $controllerClass = $request->attributes->get('_controller');

        $rateLimiter = $this->rateLimiterClassMap[$controllerClass] ?? null;
        if (null === $rateLimiter) {
            return; // no rate limit service was assigned for this controller
        }

        $token = $this->tokenStorage->getToken();
        if (
            $token instanceof TokenInterface &&
            \count(array_intersect(self::IGNORED_ROLES, $this->roleHierarchy->getReachableRoleNames(($token->getRoleNames())))) > 0) {
            return; // we ignore rate limit for site moderator & upper roles
        }

        $this->ensureRateLimiting($request, $rateLimiter, $request->getClientIp());
    }

    private function ensureRateLimiting(Request $request, RateLimiterFactory $rateLimiter, string $clientIp): void
    {

        $retryAfter = null;
        try {
            $limit = $rateLimiter->create(sprintf('rate_limit_ip_%s', $clientIp))->consume();
            $request->attributes->set('rate_limit', $limit);
            $retryAfter = $limit->getRetryAfter()
                ->setTimezone(new \DateTimeZone('UTC'))
                ->format('D, d M Y H:i:s T');
            $limit->ensureAccepted();

            $user = $this->tokenStorage->getToken()?->getUser();
            if ($user instanceof User) {
                $limit = $rateLimiter
                    ->create(sprintf('rate_limit_user_%s', $user->getId()))
                    ->consume();
                $request->attributes->set('rate_limit', $limit);
                $retryAfter = $limit->getRetryAfter()
                    ->setTimezone(new \DateTimeZone('UTC'))
                    ->format('D, d M Y H:i:s T');
                $limit->ensureAccepted();
            }

        } catch (RateLimitExceededException $e) {
            throw new TooManyRequestsHttpException($retryAfter, $e->getMessage(), $e);
        }

    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER => ['onKernelController', 1024]];
    }
}