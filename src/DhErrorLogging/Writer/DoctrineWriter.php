<?php

namespace DhErrorLogging\Writer;

use Traversable;
use Zend\Log\Writer\AbstractWriter;
use Zend\Db\Adapter\Adapter;
use Zend\Log\Exception;
use Doctrine\Common\Persistence\ObjectManager;

class DoctrineWriter extends AbstractWriter
{
    /**
     * Entity manager
     *
     * @var \Doctrine\ORM\EntityManager;
     */
    protected $entityManager = null;

    /**
     * Entity repository
     *
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $entityRepository = null;

    /**
     * Constructor
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $entityManager
     */
    public function __construct($entityManager)
    {
        $this->entityManager = $entityManager;
        $entityRepository = $entityManager->getRepository('DhErrorLogging\Doctrine\Entity\ErrorLog');
        $this->entityRepository = $entityRepository;
    }

    /**
     * Remove reference to entity repository
     *
     * @return void
     */
    public function shutdown()
    {
        $this->entityRepository = null;
    }

    /**
     * Write a message to the log.
     *
     * @param array $event event data
     * @return void
     * @throws Exception\RuntimeException
     */
    protected function doWrite(array $event)
    {
        if (null === $this->entityRepository) {
            throw new Exception\RuntimeException('Entity repository is null');
        }

        // Transform the event into fields
        $entityClass = $this->entityRepository->getClassName();
        $entity = new $entityClass();
        $entity = $this->populateEntityFromEvent($event, $entity);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    /**
     * Transform event into column for the
     *
     * @param array $eventData
     * @param $entity
     * @return object
     */
    protected function populateEntityFromEvent($eventData, $entity)
    {
        if (empty($eventData)) {
            return $entity;
        }

        if (isset($eventData['extra'])) {
            $eventData = array_merge($eventData, $eventData['extra']);
        }

        foreach ($eventData as $name => $value) {
            if ($name === 'priorityName') {
                $name = 'priority';
            }
            if (method_exists($entity, 'set' . ucfirst($name))) {
                $entity->{'set' . ucfirst($name)}($value);
            }
        }
        return $entity;
    }
}
