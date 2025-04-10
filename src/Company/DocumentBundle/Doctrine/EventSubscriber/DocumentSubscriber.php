<?php

namespace Company\DocumentBundle\Doctrine\EventSubscriber;

use Company\DocumentBundle\Service\EntityDataUpdater;
use Company\DocumentBundle\Service\EntityFileFieldCollector;
use Company\DocumentBundle\Service\EntityFileLoader;
use Company\DocumentBundle\Service\EntityFileUpdater;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;

class DocumentSubscriber implements EventSubscriber
{
    private EntityFileLoader $documentLoader;
    private EntityFileFieldCollector $entityFileFieldCollector;
    private EntityDataUpdater $entityDataUpdater;
    private EntityFileUpdater $entityFileUpdater;

    private bool $isFileManageable = false;

    public function __construct(
        EntityFileLoader $documentLoader,
        EntityFileFieldCollector $entityFileFieldCollector,
        EntityDataUpdater $entityDataUpdater,
        EntityFileUpdater $entityFileUpdater
    ) {
        $this->documentLoader = $documentLoader;
        $this->entityFileFieldCollector = $entityFileFieldCollector;
        $this->entityDataUpdater = $entityDataUpdater;
        $this->entityFileUpdater = $entityFileUpdater;
    }

    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::postLoad,
            Events::onFlush,
            Events::postFlush,
        ];
    }

    public function postLoad(LifecycleEventArgs $args): void
    {
        $this->documentLoader->loadDocumentsOfEntity($args->getEntity());
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        if ($this->isFileManageable) {
            return;
        }

        $unitOfWork = $args->getEntityManager()->getUnitOfWork();

        $this->entityFileFieldCollector->collectEntityFileFields($unitOfWork);

        $this->entityDataUpdater->update($unitOfWork);
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $entityFileFieldCollector = $this->entityFileFieldCollector;
        $collectedEntityFileFields = $entityFileFieldCollector->getCollectedEntityFileFields();
        if (!$collectedEntityFileFields->valid()) {
            return;
        }

        if (!$this->isFileManageable) {
            $this->isFileManageable = true;

            $args->getEntityManager()->flush();

            $this->isFileManageable = false;

            return;
        }

        $this->entityFileUpdater->manageCollectedDocumentFiles();

        $entityFileLoader = $this->documentLoader;
        foreach ($collectedEntityFileFields as $fileField) {
            if (EntityFileFieldCollector::REMOVABLE !== $fileField['type']) {
                $entityFileLoader->loadDocumentsOfEntity($fileField['entity']);
            }
        }

        $entityFileFieldCollector->clear();
    }
}
