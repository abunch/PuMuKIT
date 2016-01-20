<?php

namespace Pumukit\WebTVBundle\Controller;

use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Pumukit\WebTVBundle\Event\WebTVEvents;
use Pumukit\WebTVBundle\Event\ViewedEvent;

class PlayerController extends Controller
{

    protected function testBroadcast(MultimediaObject $multimediaObject, Request $request)
    {
        if (($broadcast = $multimediaObject->getBroadcast()) &&
        (Broadcast::BROADCAST_TYPE_PUB !== $broadcast->getBroadcastTypeId()) &&
        ((!($broadcastName = $request->headers->get('PHP_AUTH_USER', false))) ||
        ($request->headers->get('PHP_AUTH_PW') !== $broadcast->getPasswd()) ||
        ($broadcastName !== $broadcast->getName()))) {
            $seriesUrl = $this->generateUrl('pumukit_webtv_series_index', array('id' => $multimediaObject->getSeries()->getId()), true);
            $redReq = new RedirectResponse($seriesUrl, 302);

            return new Response($redReq->getContent(), 401, array('WWW-Authenticate' => 'Basic realm="Resource not public."'));
        }

        if ($broadcast && (Broadcast::BROADCAST_TYPE_PRI === $broadcast->getBroadcastTypeId())) {
            return new Response($this->render('PumukitWebTVBundle:Index:403forbidden.html.twig', array()), 403);
        }

        return true;
    }

    protected function updateBreadcrumbs(MultimediaObject $multimediaObject)
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
        $breadcrumbs->addMultimediaObject($multimediaObject);
    }


    protected function getIntro($queryIntro = false)
    {
        $intro = $this->container->getParameter('pumukit2.intro');

        if ($queryIntro && filter_var($queryIntro, FILTER_VALIDATE_URL)) {
            $intro = $queryIntro;
        }

        return $intro;
    }

    public function dispatchViewEvent(MultimediaObject $multimediaObject, Track $track = null)
    {
        $event = new ViewedEvent($multimediaObject, $track);
        $this->get('event_dispatcher')->dispatch(WebTVEvents::MULTIMEDIAOBJECT_VIEW, $event);
    }

    protected function getChapterMarks(MultimediaObject $multimediaObject)
    {
        //Get editor chapters for the editor template.
        //Once the chapter marks player plugin is created, this part won't be needed.
        $marks = $this->get('doctrine_mongodb.odm.document_manager')
                      ->getRepository('PumukitSchemaBundle:Annotation')
                      ->createQueryBuilder()
                      ->field('type')->equals('paella/marks')
                      ->field('multimediaObject')->equals(new \MongoId($multimediaObject->getId()))
                      ->getQuery()->getSingleResult();

        $editorChapters = array();
        if($marks) {
            $marks = json_decode($marks->getValue(), true);
        }

        foreach($marks['marks'] as $chapt) {
            $editorChapters[] = array('title' => $chapt['name'],
                                      'time' => $chapt['s']);
        }
        usort($editorChapters, function($a, $b) {
            return $a['time'] > $b['time'];
        });
        return $editorChapters;
    }
}
