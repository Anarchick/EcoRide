<?php

namespace App\Command;

use App\Repository\TravelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:travel:cleanup',
    description: 'Cancel old pending travels that are past their date',
)]
class TravelCleanupCommand extends Command
{
    public function __construct(
        private readonly TravelRepository $travelRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('EcoRide - Travel Cleanup');
        
        try {
            $cancelledCount = $this->travelRepository->cancelOldPendingTravels();
            
            $io->success(sprintf('%d trajet(s) obsolète(s) annulé(s).', $cancelledCount));
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de l\'annulation des trajets : ' . $e->getMessage());
            
            return Command::FAILURE;
        }
    }
}