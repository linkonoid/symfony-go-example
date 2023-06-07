<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{

    #[Route('/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationHelper): Response
    {
        return $this->render('@EasyAdmin/page/login.html.twig', [
            'last_username' => $authenticationHelper->getLastUsername(),
            'error' => $authenticationHelper->getLastAuthenticationError(),
            'csrf_token_intention' => 'authenticate',
            //'username_parameter' => 'username',
            //'password_parameter' => 'password',
        ]); 
    }

    #[Route('/logout', name: 'logout')]
    public function logout(): void
    {
        throw new \Exception('This should never be reached!');
    }
}
