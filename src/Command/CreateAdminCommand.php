<?php

namespace App\Command;

use App\Entity\User;
use App\Enum\RoleEnum;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[AsCommand(
    name: 'app:create:admin',
    description: 'Crée un compte administrateur si aucun admin n\'existe déjà',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Check if an admin already exists
        $existingAdmin = $this->userRepository->findByRole(RoleEnum::ADMIN);
        
        if (count($existingAdmin) > 0) {
            $io->error('Un compte administrateur existe déjà.');
            return Command::FAILURE;
        }

        $user = new User();

        $firstName = $this->askWithEntityValidation($input, $output, 'Prénom', function($value) use ($user) {
            $user->setFirstName($value);
            return $this->validator->validateProperty($user, 'firstName');
        });
        $user->setFirstName($firstName);

        $lastName = $this->askWithEntityValidation($input, $output, 'Nom', function($value) use ($user) {
            $user->setLastName($value);
            return $this->validator->validateProperty($user, 'lastName');
        });
        $user->setLastName($lastName);

        $email = $this->askWithEntityValidation($input, $output, 'Email', function($value) use ($user) {
            $user->setEmail($value);
            return $this->validator->validateProperty($user, 'email');
        });

        // Check if email already exists
        $existingUser = $this->userRepository->findOneByEmail($email);
        if ($existingUser) {
            $io->error('Un compte avec cet email existe déjà.');
            return Command::FAILURE;
        }
        $user->setEmail($email);

        $password = $this->askPasswordWithValidation($input, $output);

        $user->setUsername('Admin')
            ->setPhone('+33 6 00 00 00 00') // Default phone number
            ->addRole(RoleEnum::ADMIN);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Compte administrateur créé !');
        $io->table(
            ['Champ', 'Valeur'],
            [
                ['Prénom', $firstName],
                ['Nom', $lastName],
                ['Email', $email],
                ['Username', $user->getUsername()],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Ask a question with validation from entity property constraints
     */
    private function askWithEntityValidation(
        InputInterface $input,
        OutputInterface $output,
        string $questionText,
        callable $validationCallback
    ): string
    {
        $helper = $this->getHelper('question');
        $question = new Question("$questionText : ");

        $question->setValidator(function ($answer) use ($validationCallback) {
            $violations = $validationCallback($answer);
            if (count($violations) > 0) {
                throw new \RuntimeException($violations[0]->getMessage());
            }
            return $answer;
        });

        $question->setMaxAttempts(3);

        return $helper->ask($input, $output, $question);
    }

    /**
     * Ask for password with specific validation including PasswordStrength
     */
    private function askPasswordWithValidation(InputInterface $input, OutputInterface $output): string
    {
        $helper = $this->getHelper('question');
        $question = new Question('Mot de passe : ');
        $question->setHidden(true);
        $question->setHiddenFallback(false);

        $question->setValidator(function ($answer) {
            $constraints = [
                new Assert\Length(
                    min: 6,
                    max: 128,
                ),
                new Assert\PasswordStrength(
                    minScore: 2,
                ),
            ];

            $violations = $this->validator->validate($answer, $constraints);
            if (count($violations) > 0) {
                throw new \RuntimeException($violations[0]->getMessage());
            }
            return $answer;
        });

        $question->setMaxAttempts(3);

        return $helper->ask($input, $output, $question);
    }

}
