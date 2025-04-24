<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Image;
use App\Entity\Post;
use App\Form\CommentType;
use App\Form\ImageType;
use App\Form\PostType;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PostController extends AbstractController
{
    #[Route('/', name: 'posts')]
    public function index(PostRepository $postRepository): Response
    {
        return $this->render('post/index.html.twig', [
            'controller_name' => 'PostController',
            'posts' => $postRepository->findAll(),
        ]);
    }

    #[Route('/post/new', name: 'new_post', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $manager):Response
    {
        if(!$this->getUser() || !in_array("ROLE_ADMIN",$this->getUser()->getRoles())){
            return $this->redirectToRoute('app_login');
        }
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $post->setAuthor($this->getUser());
            $manager->persist($post);
            $manager->flush();
            return $this->redirectToRoute('posts');
        }
        return $this->render('post/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/post/show/{id}', name: 'show_post',priority: -1 )]
    public function show(Post $post, Request $request, EntityManagerInterface $manager): Response
    {
        if (!$post){
            return $this->redirectToRoute("posts");
        }
        if(!$this->getUser()){
            $this->redirectToRoute("app_login");
        }
        $comment=new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setPost($post);
            $comment->setAuthor($this->getUser());
            $manager->persist($comment);
            $manager->flush();
            return $this->redirectToRoute('show_post', ['id' => $post->getId()]);
        }


        return $this->render('post/show.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/post/edit/{id}', name: 'edit_post' )]
    public function edit(Post $post, Request $request, EntityManagerInterface $manager):Response
    {
        if (!$post){
            return $this->redirectToRoute("posts");
        }
        if(in_array("ROLE_ADMIN",$this->getUser()->getRoles())){
            $this->redirectToRoute('app_login');
        }
        if($this->getUser() !== $post->getAuthor()){
            return $this->redirectToRoute("posts");
        }

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager->persist($post);
            $manager->flush();
            return $this->redirectToRoute('posts');
        }
        return $this->render('post/edit.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }


    #[Route('/post/delete/{id}', name: 'delete_post')]
    public function delete(Post $post, EntityManagerInterface $manager):Response
    {
        if(in_array("ROLE_ADMIN",$this->getUser()->getRoles())){
            $this->redirectToRoute('app_login');
        }
        if ($post){
            $manager->remove($post);
            $manager->flush();
        }
        return $this->redirectToRoute('posts');

    }

    #[Route('/post/addimage/{id}', name: 'post_image')]
    public function addImage(Post $post, Request $request, EntityManagerInterface $manager): Response
    {
        if(!$this->getUser() || !$post)
        {
            return $this->redirectToRoute('app_login');
        }
        if($post->getAuthor() !== $this->getUser())
        {
            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        $image = new Image();
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $image->setPost($post);
            $manager->persist($image);
            $manager->flush();
            return $this->redirectToRoute('post_image', ['id' => $post->getId()]);
        }


        return $this->render('post/images.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/post/remove-image/{id}', name: 'remove_image')]
    public function removeImage(Image $image, Request $request, EntityManagerInterface $manager): Response
    {
        if(!$this->getUser() || !$image)
        {
            return $this->redirectToRoute('app_login');
        }
        if($image->getPost()->getAuthor() !== $this->getUser())
        {
            return $this->redirectToRoute('show_post', ['id' => $image->getPost()->getId()]);
        }
        $postId = $image->getPost()->getId();

        $manager->remove($image);
        $manager->flush();

        return $this->redirectToRoute('post_image', ['id' => $postId]);
    }


}
