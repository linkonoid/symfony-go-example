<?php

namespace App\Controller;

use App\Entity\Post;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/blog')]
class BlogController extends AbstractController
{
    #[Route('/', methods: ['GET'], name: 'blog_index')]
    #[Cache(smaxage: 10)]
    public function index(PostRepository $posts): Response
    {
        $postsPublished = $posts->findByStatus('on_publish');

        return $this->render('blog/index.html.twig', [
            'posts' => $postsPublished
        ]);
    }

    #[Route('/post/{id}', methods: ['GET'], name: 'blog_post')]
    public function postShow(array $_route_params, PostRepository $posts): Response
    {        
        $post = $posts->findById($_route_params['id']);

        return $this->render('blog/post_show.html.twig', ['post' => $post]);
    }
}
