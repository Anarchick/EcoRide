<?php

namespace App\Command;

use App\Service\GeocodingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Pre-cache geocoding coordinates for all unique cities in the database
 * This command should be run periodically to warm up the cache
 */
#[AsCommand(
    name: 'app:geocache:warmup',
    description: 'Pré-charge les coordonnées GPS des villes fréquentes dans MongoDB'
)]
class GeocacheWarmupCommand extends Command
{
    public function __construct(
        private readonly GeocodingService $geocodingService,
        private readonly EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force re-geocoding even if already cached'
            )
            ->setHelp(
                <<<'HELP'
This command pre-loads the MongoDB cache with the GPS coordinates of all 
the cities present in the Travels table (departure and arrival).

Usage:
    php bin/console app:geocache:warmup           # GGeocode only uncached cities
    php bin/console app:geocache:warmup --force   # Force re-geocoding of all cities

Attention : Nominatim limit to 1 request/second, this command can take time.
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = $input->getOption('force');

        $io->title('EcoRide - Warmup of the Geocoding Cache');

        // Step 1: Fetch all unique cities from database
        $io->section('Fetching cities from the database...');

        try {
            $cities = $this->getUniqueCities();
        } catch (\Exception $e) {
            $io->error('Error fetching cities: ' . $e->getMessage());
            return Command::FAILURE;
        }

        if (empty($cities)) {
            $io->warning('No cities found in the database.');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Number of unique cities found: %d', count($cities)));
        
        if (!$io->confirm('Continue the preloading?', true)) {
            $io->note('Operation cancelled.');
            return Command::SUCCESS;
        }

        // Step 2: Geocode each city
        $io->section('Geocoding cities...');
        $io->progressStart(count($cities));

        $successCount = 0;
        $failureCount = 0;
        $failedCities = [];

        foreach ($cities as $city) {
            $io->progressAdvance();

            // Skip if already cached (unless --force)
            if (!$force) {
                // This will use cache if available
                $coords = $this->geocodingService->geocode($city);
                
                if ($coords !== null) {
                    $successCount++;
                    continue;
                }
            }

            // Force geocoding (will update cache)
            try {
                $coords = $this->geocodingService->geocode($city);

                if ($coords !== null) {
                    $successCount++;
                } else {
                    $failureCount++;
                    $failedCities[] = $city;
                }

            } catch (\Exception $e) {
                $failureCount++;
                $failedCities[] = $city;
                $io->error(PHP_EOL . "Failed to geocode '{$city}': " . $e->getMessage());
            }

            // Respect Nominatim rate limit (1 req/sec)
            sleep(1);
        }

        $io->progressFinish();

        // Step 3: Display results
        $io->section('Results');

        $io->success([
            sprintf('✅ Success: %d', $successCount),
            sprintf('❌ Failures: %d', $failureCount),
        ]);

        if (!empty($failedCities)) {
            $io->warning('Failed cities:');
            $io->listing($failedCities);
        }

        return $failureCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Get all unique city names from Travel table
     * 
     * @return array<string>
     */
    private function getUniqueCities(): array
    {
        $sql = '
            SELECT DISTINCT city
            FROM (
                SELECT departure AS city FROM travels
                UNION
                SELECT arrival AS city FROM travels
            ) AS all_cities
            ORDER BY city
        ';

        $stmt = $this->em->getConnection()->prepare($sql);
        $result = $stmt->executeQuery();

        return array_column($result->fetchAllAssociative(), 'city');
    }
}
