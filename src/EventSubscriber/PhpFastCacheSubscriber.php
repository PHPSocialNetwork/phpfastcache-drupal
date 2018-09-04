<?php
namespace Drupal\phpfastcache\EventSubscriber;

use Phpfastcache\CacheManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PhpFastCacheSubscriber implements EventSubscriberInterface {

    public function initPhpFastCacheAutoload(GetResponseEvent $event) {

    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        $events[KernelEvents::REQUEST][] = array('initPhpFastCacheAutoload');
        return $events;
    }
}
