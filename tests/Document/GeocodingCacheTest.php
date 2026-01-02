<?php

namespace App\Tests\Document;

use App\Document\GeocodingCache;
use PHPUnit\Framework\TestCase;

class GeocodingCacheTest extends TestCase
{
    public function testDocumentCanBeCreated(): void
    {
        // Act
        $cache = new GeocodingCache();

        // Assert
        $this->assertInstanceOf(GeocodingCache::class, $cache);
        $this->assertInstanceOf(\DateTimeInterface::class, $cache->getCreatedAt());
    }

    public function testSettersAndGetters(): void
    {
        // Arrange
        $cache = new GeocodingCache();
        $expiresAt = new \DateTime('+3 months');

        // Act
        $cache->setCityName('paris');
        $cache->setLatitude(48.8566);
        $cache->setLongitude(2.3522);
        $cache->setExpiresAt($expiresAt);

        // Assert
        $this->assertEquals('paris', $cache->getCityName());
        $this->assertEquals(48.8566, $cache->getLatitude());
        $this->assertEquals(2.3522, $cache->getLongitude());
        $this->assertEquals($expiresAt, $cache->getExpiresAt());
    }

    public function testCreatedAtIsSetAutomatically(): void
    {
        // Arrange
        $beforeCreation = new \DateTimeImmutable('-1 second');
        
        // Act
        $cache = new GeocodingCache();
        
        // Assert
        $afterCreation = new \DateTimeImmutable('+1 second');
        $this->assertGreaterThanOrEqual($beforeCreation, $cache->getCreatedAt());
        $this->assertLessThanOrEqual($afterCreation, $cache->getCreatedAt());
    }

    public function testSetCreatedAtOverridesDefault(): void
    {
        // Arrange
        $cache = new GeocodingCache();
        $customDate = new \DateTimeImmutable('2025-01-01 12:00:00');

        // Act
        $cache->setCreatedAt($customDate);

        // Assert
        $this->assertEquals($customDate, $cache->getCreatedAt());
    }

    public function testFluentInterface(): void
    {
        // Arrange
        $cache = new GeocodingCache();

        // Act
        $result = $cache
            ->setCityName('lyon')
            ->setLatitude(45.7640)
            ->setLongitude(4.8357)
            ->setExpiresAt(new \DateTime('+3 months'));

        // Assert
        $this->assertSame($cache, $result);
    }

    public function testCoordinatesPrecision(): void
    {
        // Arrange
        $cache = new GeocodingCache();

        // Act: Set high-precision coordinates
        $cache->setLatitude(48.856614);
        $cache->setLongitude(2.3522219);

        // Assert: Verify full precision is maintained
        $this->assertEquals(48.856614, $cache->getLatitude());
        $this->assertEquals(2.3522219, $cache->getLongitude());
    }

    public function testNegativeCoordinates(): void
    {
        // Arrange
        $cache = new GeocodingCache();

        // Act: Set negative coordinates (Southern/Western hemisphere)
        $cache->setLatitude(-33.8688);  // Sydney
        $cache->setLongitude(151.2093);

        // Assert
        $this->assertEquals(-33.8688, $cache->getLatitude());
        $this->assertEquals(151.2093, $cache->getLongitude());
    }

    public function testExpirationDateInFuture(): void
    {
        // Arrange
        $cache = new GeocodingCache();
        $futureDate = new \DateTime('+3 months');

        // Act
        $cache->setExpiresAt($futureDate);

        // Assert
        $this->assertGreaterThan(new \DateTime(), $cache->getExpiresAt());
    }

    public function testIdIsGeneratedAfterPersistence(): void
    {
        // Arrange
        $cache = new GeocodingCache();

        // Act: Simulate MongoDB setting the ID
        $reflection = new \ReflectionClass($cache);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($cache, '507f1f77bcf86cd799439011');

        // Assert
        $this->assertEquals('507f1f77bcf86cd799439011', $cache->getId());
    }

    /**
     * Test that document structure matches expected MongoDB schema
     */
    public function testDocumentStructure(): void
    {
        // Arrange
        $cache = new GeocodingCache();
        $cache->setCityName('marseille');
        $cache->setLatitude(43.2965);
        $cache->setLongitude(5.3698);
        $cache->setExpiresAt(new \DateTime('+3 months'));

        // Act: Check all required fields are present
        $reflection = new \ReflectionClass($cache);
        
        // Assert: Verify all expected properties exist
        $expectedProperties = ['id', 'cityName', 'latitude', 'longitude', 'createdAt', 'expiresAt'];
        foreach ($expectedProperties as $propertyName) {
            $this->assertTrue(
                $reflection->hasProperty($propertyName),
                "Property '$propertyName' should exist"
            );
        }
    }

    /**
     * Test MongoDB annotations are properly configured
     */
    public function testDocumentHasMongoDBAnnotations(): void
    {
        // Arrange & Act
        $reflection = new \ReflectionClass(GeocodingCache::class);
        $attributes = $reflection->getAttributes();

        // Assert: Should have at least Document and Index attributes
        $this->assertGreaterThanOrEqual(3, count($attributes), 
            'Document should have MongoDB Document and Index attributes'
        );

        // Check for Document attribute
        $documentAttributes = $reflection->getAttributes(\Doctrine\ODM\MongoDB\Mapping\Annotations\Document::class);
        $this->assertCount(1, $documentAttributes, 'Should have exactly one Document attribute');
    }
}
