<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Post;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        return $this->redirect($adminUrlGenerator->setController(PostCrudController::class)->generateUrl());
    }

    public function configureCrud(): Crud
    {
        return Crud::new()
            ->setDateTimeFormat('medium', 'short');
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        return parent::configureUserMenu($user)
            ->setName($user->getFullName());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Пример блога');
    }

    public function configureMenuItems(): iterable
    {
        return [
            MenuItem::linkToDashboard('Админ-панель', 'fa fa-home'),
 
            MenuItem::section('Блог'),
            //MenuItem::linkToCrud('Категории', 'fa fa-tags', Category::class),
            MenuItem::linkToCrud('Посты', 'fa fa-file-text', Post::class),

            MenuItem::section('Пользователи')
                ->setPermission('ROLE_EDITOR'),
            MenuItem::linkToCrud('Пользователи', 'fa fa-user', User::class)
                ->setPermission('ROLE_EDITOR'),
            //MenuItem::linkToCrud('Роли', 'fa fa-comment', Rule::class),           
        ];
    }
}
