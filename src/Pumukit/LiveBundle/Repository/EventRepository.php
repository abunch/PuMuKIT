<?php

namespace Pumukit\LiveBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Pumukit\LiveBundle\Document\Live;

/**
 * EventRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EventRepository extends DocumentRepository
{
    /**
     * Find next events.
     */
    public function findNextEvents()
    {
        $now = new \DateTime('now');

        return $this->createQueryBuilder()
            ->field('display')->equals(true)
            ->field('date')->gte($now)
            ->sort('date', 1)
            ->getQuery()->execute();
    }

    /**
     * Find next event.
     */
    public function findNextEvent()
    {
        $now = new \DateTime('now');

        return $this->createQueryBuilder()
            ->field('display')->equals(true)
            ->field('date')->gte($now)
            ->sort('date', 1)
            ->getQuery()->getSingleResult();
    }

    /**
     * Find event in a month.
     */
    public function findInMonth($month, $year)
    {
        $start = new \DateTime(sprintf('%s-%s-01', $year, $month));
        $end = clone $start;
        $end = $end->modify('+1 month');

        return $this->createQueryBuilder()
            ->field('date')->gte($start)
            ->field('date')->lt($end)
            ->sort('date', 1)
            ->getQuery()->execute();
    }

    /**
     * Find current events.
     *
     * @param $limit int|null
     * @param $marginBefore int defalt 0
     * @param $marginAfter int default 0
     *
     * @return array of Events
     */
    public function findCurrentEvents($limit = null, $marginBefore = 0, $marginAfter = 0)
    {
        $dmColl = $this->dm->getDocumentCollection('PumukitLiveBundle:Event');

        $nowWithMarginBefore = new \MongoDate(strtotime(sprintf('%s minute', $marginBefore)));
        $nowWithMarginAfter = new \MongoDate(strtotime(sprintf('-%s minute', $marginAfter)));
        $pipeline = array(
            array('$match' => array('display' => true)),
            array('$project' => array('date' => true, 'end' => array('$add' => array('$date', array('$multiply' => array('$duration', 60000)))))),
            array('$match' => array('$and' => array(array('date' => array('$lte' => $nowWithMarginBefore)), array('end' => array('$gte' => $nowWithMarginAfter))))),
        );

        if ($limit) {
            $pipeline[] = array('$limit' => $limit);
        }
        $aggregation = $dmColl->aggregate($pipeline, array('cursor' => array()));

        if (0 === $aggregation->count()) {
            return array();
        }

        $ids = array_map(function ($e) {
            return $e['_id'];
        }, $aggregation->toArray());

        return $this->createQueryBuilder()
            ->field('_id')->in($ids)
            ->getQuery()->execute();
    }

    /**
     * Find future and not finished.
     *
     * @param int  $limit
     * @param Date $date
     * @param Live $live  Find only events of a live channel
     *
     * @return Cursor
     */
    public function findFutureAndNotFinished($limit = null, $date = null, Live $live = null)
    {
        // First: look if there is a current live event broadcasting
        // for setting datetime minus duration
        if (!$date) {
            $currentDatetime = new \DateTime('now');
            $startDay = new \DateTime('now');
            $finishDay = new \DateTime('now');
        } else {
            $currentDatetime = new \DateTime($date->format('Y-m-d H:s:i'));
            $startDay = new \DateTime($date->format('Y-m-d H:s:i'));
            $finishDay = new \DateTime($date->format('Y-m-d H:s:i'));
        }
        $startDay->setTime(0, 0, 0);
        $finishDay->setTime(23, 59, 59);

        $currentDayEventsQB = $this->createQueryBuilder()
            ->field('display')->equals(true)
            ->field('date')->gte($startDay)
            ->field('date')->lte($finishDay)
            ->sort('date', 1);

        if ($live) {
            $currentDayEventsQB->field('live')->references($live);
        }

        $currentDayEvents = $currentDayEventsQB->getQuery()->execute();

        $duration = 0;
        foreach ($currentDayEvents as $event) {
            $eventDate = new \DateTime($event->getDate()->format('Y-m-d H:i:s'));
            if (($eventDate < $currentDatetime) && ($currentDatetime < $eventDate->add(new \DateInterval('PT'.$event->getDuration().'M')))) {
                $duration = $event->getDuration();
            }
        }
        $currentDatetime->sub(new \DateInterval('PT'.$duration.'M'));

        // Second: look for current and next events
        $qb = $this->createQueryBuilder()
            ->field('display')->equals(true)
            ->field('date')->gte($currentDatetime)
            ->sort('date', 1);

        if ($live) {
            $qb->field('live')->references($live);
        }

        if ($limit) {
            $qb->limit($limit);
        }

        return $qb->getQuery()->execute();
    }

    /**
     * Find one by hours event.
     *
     * @param int  $hours
     * @param Date $date
     *
     * @return Cursor
     */
    public function findOneByHoursEvent($hours = null, $date = null)
    {
        if (!$date) {
            $currentDatetime = new \DateTime('now');
            $hoursDatetime = new \DateTime('now');
            $startDay = new \DateTime('now');
            $finishDay = new \DateTime('now');
        } else {
            $currentDatetime = new \DateTime($date->format('Y-m-d H:s:i'));
            $hoursDatetime = new \DateTime($date->format('Y-m-d H:s:i'));
            $startDay = new \DateTime($date->format('Y-m-d H:s:i'));
            $finishDay = new \DateTime($date->format('Y-m-d H:s:i'));
        }
        $hoursDatetime->add(new \DateInterval('PT'.$hours.'H'));
        $startDay->setTime(0, 0, 0);
        $finishDay->setTime(23, 59, 59);

        $currentDayEvents = $this->createQueryBuilder()
            ->field('display')->equals(true)
            ->field('date')->gte($startDay)
            ->field('date')->lte($finishDay)
            ->sort('date', 1)
            ->getQuery()->execute();

        foreach ($currentDayEvents as $event) {
            $eventDate = new \DateTime($event->getDate()->format('Y-m-d H:i:s'));
            if (($eventDate <= $hoursDatetime) && ($currentDatetime <= $eventDate->add(new \DateInterval('PT'.$event->getDuration().'M')))) {
                return $event;
            }
        }

        return null;
    }
}
