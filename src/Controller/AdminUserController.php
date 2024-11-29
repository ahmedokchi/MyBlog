<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminUserController extends AbstractController
{
    #[Route('/admin/users', name: 'admin_user_list')]
    public function list(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $userRepository->findAll();

        return $this->render('admin/user_list.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/user/activate/{id}', name: 'admin_user_activate')]
    public function activate(User $user, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user->setIsActive(true);

        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur activé avec succès.');

        return $this->redirectToRoute('admin_user_list');
    }

    #[Route('/admin/user/deactivate/{id}', name: 'admin_user_deactivate')]
    public function deactivate(User $user, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user->setIsActive(false);

        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur désactivé avec succès.');

        return $this->redirectToRoute('admin_user_list');
    }
}
