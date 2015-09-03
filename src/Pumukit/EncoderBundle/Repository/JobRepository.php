<?php

namespace Pumukit\EncoderBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Pumukit\EncoderBundle\Document\Job;

/**
 * JobRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class JobRepository extends DocumentRepository
{
    /**
     * Create query builder for all jobs with given status
     */
    public function createQueryWithStatus(array $status, $sort = array())
    {
        $qb = $this->createQueryBuilder()
            ->field('status')->in($status);

        if (null != $sort) {
          $qb->sort($sort);
        }
        
        return $qb;
    }

    /**
     * Find all jobs with given status
     */
    public function findWithStatus(array $status, $sort = array())
    {
        return $this->createQueryWithStatus($status, $sort)
          ->getQuery()
          ->execute();
    }    

    /**
     * Find the job with higher priority with given status
     */
    public function findHigherPriorityWithStatus(array $status)
    {
        return $this->createQueryBuilder()
          ->field('status')->in($status)
          ->sort('priority', 'desc')
          ->sort('timeini', 'asc')
          ->getQuery()
          ->getSingleResult();
    }

    /**
     * Find not finished jobs with given multimedia object id
     */
    public function findNotFinishedByMultimediaObjectId($mmId)
    {
        return $this->createQueryBuilder()
          ->field('mm_id')->equals($mmId)
          ->field('status')->notEqual(Job::STATUS_FINISHED)
          ->getQuery()
          ->execute();
    }

    /**
     * Find all jobs with given status and multimedia object id
     */
    public function findByStatusAndMultimediaObjectId($status, $multimediaObjectId)
    {
        return $this->createQueryBuilder()
          ->field('mm_id')->equals($multimediaObjectId)
          ->field('status')->equals($status)
          ->getQuery()
          ->execute();
    }

    /**
     * Find all jobs with given multimedia object id
     */
    public function findByMultimediaObjectId($multimediaObjectId)
    {
        return $this->createQueryBuilder()
          ->field('mm_id')->equals($multimediaObjectId)
          ->getQuery()
          ->execute();
    }

    /**
     * Find all jobs with given multimedia object id and profile
     */
    public function findByMultimediaObjectIdAndProfile($multimediaObjectId, $profile)
    {
        return $this->createQueryBuilder()
          ->field('mm_id')->equals($multimediaObjectId)
          ->field('profile')->equals($profile)
          ->getQuery()
          ->execute();
    }
}