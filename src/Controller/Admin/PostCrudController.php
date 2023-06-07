<?php

namespace App\Controller\Admin;

use App\Entity\Post;
use App\Entity\User;
use Symfony\Component\Security\Core\Security;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\HiddenField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class PostCrudController extends AbstractCrudController
{
    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security)
    {
       $this->security = $security;
       $this->author = $this->security->getUser();

    }

    public static function getEntityFqcn(): string
    {
        return Post::class;
    }

    public function createEntity(string $entityFqcn)
    {
        $post = new Post();
        $post->setAuthor($this->getUser());
        $post->setStatus('on_write');

        return $post;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['publishedAt' => 'DESC'])
            ->setSearchFields(['title'])
            ->setPaginatorPageSize(10);
    }

    public function configureActions(Actions $actions): Actions
    {
        return parent::configureActions($actions)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->remove(Crud::PAGE_INDEX, Action::DELETE) 
            ->setPermission(Action::NEW, 'ROLE_AUTHOR')
            ->setPermission(Action::DELETE, 'ROLE_AUTHOR')
            ->setPermission(Action::DETAIL, 'ROLE_EDITOR')
            ->setPermission(Action::EDIT, 'ROLE_EDITOR');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('title')
            ->add('author')
            ->add('publishedAt')
        ;
    }

    public function configureFields(string $pageName): iterable
    {    
        return [
            TextField::new('title'),
            TextField::new('description'),
            TextEditorField::new('content')
                ->hideOnIndex()
                ->setNumOfRows(8)
                ->setPermission('ROLE_EDITOR'),
            ChoiceField::new('status')->setChoices([
                'Write' => 'on_write',
                'Moderate' => 'on_moderate',
                'Publish' => 'on_publish',
            ])
                ->setPermission('ROLE_EDITOR'),
            TextEditorField::new('moderator_comment')
                ->hideOnIndex()
                ->setNumOfRows(3)
                ->setPermission('ROLE_EDITOR'),
            DateTimeField::new('publishedAt'),
        ];

    }

    //Фильтруем список по статусу
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        if ($this->isGranted('ROLE_AUTHOR')) {
            $queryBuilder
                ->andWhere('entity.author = :author')
                ->setParameter('author', $this->getUser()->getId());
        }

        if ($this->isGranted('ROLE_MODERATOR')) {
            $queryBuilder
                ->andWhere('entity.status = :status')
                ->setParameter('status', 'on_moderate');
        }

        if ($this->isGranted('ROLE_GUEST')) {
            $queryBuilder
                ->andWhere('entity.status = :status')
                ->setParameter('status', 'on_publish');
        }

        return $queryBuilder;
    }
}
