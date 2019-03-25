<?php

namespace Pumukit\SchemaBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * BroadcastRepository.
 *
 * @deprecated in version 2.3
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below
 */
class BroadcastRepository extends DocumentRepository
{
    /**
     * Find default selected broadcast.
     *
     * @return object
     */
    public function findDefaultSel()
    {
        return $this->createQueryBuilder()
            ->field('default_sel')->equals(true)
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * Find public broadcast.
     *
     * @return object
     */
    public function findPublicBroadcast()
    {
        return $this->createQueryBuilder()
            ->field('broadcast_type_id')->equals('public')
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * Find distinct ids by broadcast type id.
     *
     * @param $broadcastTypeId
     *
     * @return mixed
     */
    public function findDistinctIdsByBroadcastTypeId($broadcastTypeId)
    {
        return $this->createQueryBuilder()
            ->field('broadcast_type_id')->equals($broadcastTypeId)
            ->distinct('_id')
            ->getQuery()
            ->execute();
    }
}