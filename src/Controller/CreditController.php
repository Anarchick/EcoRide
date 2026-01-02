<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\CurrencyEnum;
use App\Enum\RoleEnum;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/credits', name: 'app_credits_')]
#[IsGranted(RoleEnum::USER->value)]
final class CreditController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function creditsHistory(
        #[CurrentUser] User $user,
        TransactionRepository $transactionRepository,
        Request $request
    ): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $pageOffset = ($page - 1) * 10;
        $transactions = $transactionRepository->findBy(['user' => $user], ['createAt' => 'DESC'], 10, $pageOffset);
        $totalTransactions = $transactionRepository->count(['user' => $user]);

        return $this->render('profile/credits/index.html.twig', [
            'credits' => $user->getCredits(),
            'transactions' => $transactions,
            'totalTransactions' => $totalTransactions,
        ]);
    }

    #[Route('/buy', name: 'buy')]
    public function creditsBuy(
        #[CurrentUser] User $user,
        EntityManagerInterface $em
    ): Response
    {
        $price = 10; // placeholder
        $amount = $price * 10;
        /** @var Transaction */
        $transaction = (new Transaction())
            ->setUser($user)
            ->setCredits($amount)
            ->setPrice($price)
            ->setCurrency(CurrencyEnum::EUR);
        $user->addCredits($amount);

        $em->persist($user);
        $em->persist($transaction);
        $em->flush();

        $this->addFlash('success', sprintf('Vous avez acheté %d crédits! pour %d EUR', $amount, $price));
        return $this->redirectToRoute('app_credits_index');
    }
}
