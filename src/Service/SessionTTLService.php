<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Manages session data with TTL (Time To Live)
 */
class SessionTTLService
{
    private const DEFAULT_TTL = 1800; // 30 minutes

    public function __construct(
        private readonly RequestStack $requestStack
    ) {}

    /**
     * Store data with expiration timestamp (seconds)
     */
    public function set(string $key, mixed $data, int $ttl = self::DEFAULT_TTL): void
    {
        $session = $this->requestStack->getSession();
        
        $session->set($key, [
            'data' => $data,
            'expire_at' => (new \DateTimeImmutable())->modify("+{$ttl} seconds")->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get data if not expired, null otherwise
     */
    public function get(string $key): mixed
    {
        $session = $this->requestStack->getSession();
        $wrapper = $session->get($key);

        if (!$wrapper || !isset($wrapper['expire_at'], $wrapper['data'])) {
            return null;
        }

        $expireAt = new \DateTimeImmutable($wrapper['expire_at']);
        
        if (new \DateTimeImmutable() > $expireAt) {
            $session->remove($key);
            return null;
        }

        return $wrapper['data'];
    }

    public function remove(string $key): void
    {
        $this->requestStack->getSession()->remove($key);
    }
}
