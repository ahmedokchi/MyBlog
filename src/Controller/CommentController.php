<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use Symfony\Component\HttpFoundation\Request;
use App\Form\CommentType;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Security\Http\Attribute\IsGranted;

class CommentController extends AbstractController
{
    #[Route('/comment/delete/{id}', name: 'app_comment_delete', methods: ['GET'])]
    public function delete(
        int $id,
        CommentRepository $commentRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $comment = $commentRepository->find($id);

        if (!$comment) {
            throw $this->createNotFoundException("Le commentaire avec l'ID $id n'existe pas.");
        }

        $entityManager->remove($comment);
        $entityManager->flush();
        $this->addFlash('success', 'Commentaire supprimé avec succès.');

        return $this->redirectToRoute('app_post', ['id' => $comment->getPost()->getId()]);
    }



    #[Route('/comment/{id}/edit', name: 'app_comment_edit', methods: ['GET', 'POST'])]
    public function edit(Comment $comment, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le commentaire a été modifié avec succès.');

            return $this->redirectToRoute('app_post', ['id' => $comment->getPost()->getId()]);
        }


        return $this->render('comment/edit.html.twig', [
            'form' => $form->createView(),
            'comment' => $comment,
        ]);
    }
}




