<?php

namespace App\Event;

use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\TransferException;

class PostChangeSubscriber implements EventSubscriberInterface
{
    private $myLogger;

    public function __construct(LoggerInterface $myLogger)
    {
        $this->myLogger = $myLogger;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
            Events::postRemove,            
        ];
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->onChange('persist', $args->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->onChange('update', $args->getObject());
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $this->onChange('remove', $args->getObject());
    }

    public function onChange(string $action, mixed $entity): void
    {
        if (!$entity instanceof Post) {
            return;
        }

        $actionMap = [
            'persist' => 'create',
            'update' => 'update',
            'remove' => 'delete',
        ];

        $logger = $this->myLogger;

        //Запросы шлём асинхронно через Guzzle

        $client = new Client([
            'base_uri' => 'http://localhost:8080',
            'timeout' => 20,
        ]);
        $promise = $client->postAsync('/analytics', [
            'json' => [
                "authorId" => $entity->getAuthor()->getId(),
                "postId" => $entity->getId(),
                "postTitle" => $entity->getTitle(),
                "action" => $actionMap[$action],
            ],
        ])->then(           
            function (Response $res) use ($logger) {
                $response = json_decode($res->getBody()->getContents());
                $logger->info($res->getStatusCode());
                return $response;
            },
            function (TransferException $e) use ($logger) {
                $response = [];
                $logger->info($e->getMessage());
                return $response;
            },
        );

        $response = $promise->wait();
    }
}
