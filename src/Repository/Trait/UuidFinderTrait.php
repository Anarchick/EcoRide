<?php

namespace App\Repository\Trait;

use Symfony\Component\Uid\Uuid;

/**
 * @template T
 */
trait UuidFinderTrait
{
    /**
     * Find an entity by their UUID.
     * Accepts both 32-character string (without dashes) and standard UUID format.
     * Returns null if the UUID is invalid or not found.
     * @param string|Uuid $uuid
     * @return T|null
     */
    public function getByUuid(string|Uuid $uuid): mixed
    {
        if (is_string($uuid) && strlen($uuid) == 32) {
            $uuid = sprintf(
                '%s-%s-%s-%s-%s', // RFC4122
                substr($uuid, 0, 8),
                substr($uuid, 8, 4),
                substr($uuid, 12, 4),
                substr($uuid, 16, 4),
                substr($uuid, 20, 12)
            );
        }

        try {
            $uuid = Uuid::fromString($uuid);
        } catch (\InvalidArgumentException) {
            return null;
        }

        return $this->findOneBy(['uuid' => $uuid]);
    }
}