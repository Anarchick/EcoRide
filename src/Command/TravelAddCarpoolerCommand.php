<?php

namespace App\Command;

use App\Entity\Travel;
use App\Entity\User;
use App\Repository\TravelRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:travel:carpool',
    description: 'Add random carpoolers to a travel (dev environment only)',
)]
class TravelAddCarpoolerCommand extends Command
{
    public function __construct(
        private readonly TravelRepository $travelRepository,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $em,
        private readonly KernelInterface $kernel
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('uuid', InputArgument::REQUIRED, 'Travel UUID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->kernel->getEnvironment() !== 'dev') {
            $io->error('This command can only be run in dev environment.');
            return Command::FAILURE;
        }

        $uuid = $input->getArgument('uuid');

        /** @var Travel|null $travel */
        $travel = $this->travelRepository->getByUuid($uuid);

        if (!$travel) {
            $io->error(sprintf('Travel with UUID "%s" not found.', $uuid));
            return Command::FAILURE;
        }

        $availableSlots = $travel->getAvailableSlots();

        if ($availableSlots === 0) {
            $io->warning('No available slots for this travel.');
            return Command::SUCCESS;
        }

        // Ask how many carpoolers to add
        $helper = $this->getHelper('question');
        $question = new Question(sprintf('How many carpoolers? (max %d): ', $availableSlots), 1);
        $question->setValidator(function ($answer) use ($availableSlots) {
            if (!is_numeric($answer)) {
                throw new \RuntimeException(
                    sprintf('Veuillez entrer un nombre entre 1 et %d.', $availableSlots)
                );
            }
            $answer = (int) $answer;

            if ($answer < 1) {
                $answer = 1;
            } elseif ($answer > $availableSlots) {
                $answer = $availableSlots;
            }
            return $answer;
        });

        $numberOfCarpoolers = $helper->ask($input, $output, $question);

        // Get all users except the driver
        $allUsers = $this->userRepository->findAll();
        $eligibleUsers = array_filter($allUsers, function (User $user) use ($travel) {
            return $user->getUuid() !== $travel->getDriver()->getUuid() 
                && !$travel->isCarpooler($user)
                && !$user->isModerator();
        });

        if (count($eligibleUsers) < $numberOfCarpoolers) {
            $io->error(sprintf(
                'Not enough eligible users. Found %d, need %d.',
                count($eligibleUsers),
                $numberOfCarpoolers
            ));
            return Command::FAILURE;
        }

        // Shuffle and take random users
        shuffle($eligibleUsers);
        $selectedUsers = array_slice($eligibleUsers, 0, $numberOfCarpoolers);

        $successCount = 0;

        foreach ($selectedUsers as $user) {
            $slots = 1; // Each carpooler takes 1 slot
            $cost = $travel->getCost() * $slots;
            $user->addCredits($cost);

            try {
                $carpooler = $travel->join($user, $slots, $cost);
                
                if ($carpooler !== null) {
                    $this->em->persist($carpooler);
                    $successCount++;
                    $io->success(sprintf(
                        'Added carpooler: %s (%d slot(s), %d credits)',
                        $user->getUsername(),
                        $slots,
                        $cost
                    ));
                }
            } catch (\InvalidArgumentException $e) {
                $io->warning(sprintf(
                    'Failed to add carpooler %s (%s): %s',
                    $user->getUsername(),
                    $user->getEmail(),
                    $e->getMessage()
                ));
            }

            if ($travel->getAvailableSlots() === 0) {
                break; // No more slots available
            }
        }

        $this->em->flush();

        $io->success(sprintf(
            'Successfully added %d carpooler(s) to the travel.',
            $successCount
        ));

        return Command::SUCCESS;
    }
}
