<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentType;
use App\Entity\Post;
use App\Repository\PostRepository;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\PostType;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class PostController extends AbstractController
{
    #[Route('/posts', name: 'app_posts')]
    public function list(PostRepository $postRepository): Response
    {
        $posts = $postRepository->getAllPosts();

        return $this->render('post/index.html.twig', [
            'controller_name' => 'PostController',
            'posts' => $posts,
        ]);
    }

    #[Route('/post/{id}', name: 'app_post', requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        PostRepository $postRepository,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $post = $postRepository->find($id);

        if (!$post) {
            throw $this->createNotFoundException("Le post avec l'ID $id n'existe pas.");
        }

        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setPost($post);
            $comment->setUser($this->getUser());
            $comment->setCreatedAt(new \DateTime());
            $comment->setStatus('pending');

            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Votre commentaire a été ajouté et est en attente de modération.');

            return $this->redirectToRoute('app_post', ['id' => $post->getId()]);
        }

        return $this->render('post/show.html.twig', [
            'post' => $post,
            'comments' => $post->getComments(),
            'form' => $form->createView(),
            'is_logged_in' => $this->getUser() !== null,
        ]);
    }

    #[Route('/post_add/', name: 'app_post_add')]
    public function postForm(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $post = new Post();
        $form = $this->createForm(PostType::class, $post);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('picture')->getData();

            if ($file) {
                $newFilename = uniqid() . '.' . $file->guessExtension();

                try {
                    $file->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    throw new \Exception("Erreur lors de l'upload de l'image : {$e->getMessage()}");
                }

                $post->setPicture($newFilename);
            }

            $entityManager->persist($post);
            $entityManager->flush();

            return $this->redirectToRoute('app_posts');
        }

        return $this->render('post/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/post/edit/{id}', name: 'app_post_edit')]
    public function edit(


        int $id,
        PostRepository $postRepository,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {

        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $post = $postRepository->find($id);

        if (!$post) {
            throw $this->createNotFoundException("Le post avec l'ID $id n'existe pas.");
        }

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('picture')->getData();

            if ($file) {
                if ($post->getPicture()) {
                    $oldImagePath = $this->getParameter('images_directory') . '/' . $post->getPicture();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                $newFilename = uniqid() . '.' . $file->guessExtension();

                try {
                    $file->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    throw new \Exception("Erreur lors de l'upload de l'image : {$e->getMessage()}");
                }

                $post->setPicture($newFilename);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_posts');
        }

        return $this->render('post/edit.html.twig', [
            'form' => $form->createView(),
            'post' => $post,
        ]);
    }

    #[Route('/post/delete/{id}', name: 'app_post_delete')]
    public function delete(
        int $id,
        PostRepository $postRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $post = $postRepository->find($id);

        if (!$post) {
            throw $this->createNotFoundException("Le post avec l'ID $id n'existe pas.");
        }

        if ($post->getPicture()) {
            $imagePath = $this->getParameter('images_directory') . '/' . $post->getPicture();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $entityManager->remove($post);
        $entityManager->flush();

        $this->addFlash('success', 'Post et ses commentaires supprimés avec succès.');

        return $this->redirectToRoute('app_posts');
    }
}
