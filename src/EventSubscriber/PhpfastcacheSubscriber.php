<?php

namespace Drupal\phpfastcache\EventSubscriber;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Cache\CacheFactoryInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Utility\Error;
use Drupal\phpfastcache\Cache\PhpfastcacheBackendFactory;
use Phpfastcache\Exceptions\PhpfastcacheRootException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class PhpfastcacheSubscriber
 *
 * @package Drupal\phpfastcache\EventSubscriber
 */
class PhpfastcacheSubscriber implements EventSubscriberInterface {

  /**
   * The config factory used by the config entity query.
   *
   * @var CacheFactoryInterface
   */
  protected $cacheFactory;

  /**
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  public function __construct(CacheFactoryInterface $cacheFactory, ModuleHandler $moduleHandler) {
    $this->cacheFactory = $cacheFactory;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   */
  public function onPhpfastcacheException(GetResponseForExceptionEvent $event) {
    /**
     * Make sure that we handle the exceptions
     * that we are allowed to manage
     */
    if ($this->cacheFactory instanceof PhpfastcacheBackendFactory) {
      $settings      = $this->cacheFactory->getSettingsFromDatabase();
      if ($settings[ 'phpfastcache_env' ] === PhpfastcacheBackendFactory::ENV_DEV
          && $event->getException() instanceof PhpfastcacheRootException
          && $this->moduleHandler->moduleExists('devel')
      ) {
        $culpritDriver = \ucfirst($settings[ 'phpfastcache_default_driver' ]);
        /**
         * Preparing message args
         */
        $error                  = Error::decodeException($event->getException());
        $error[ '@short_type' ] = \substr(\strrchr('\\' . \ltrim($error[ '%type' ], '\\'), '\\'), 1);
        $error[ '@type' ]       = $error[ '%type' ];
        $error[ '@file' ]       = $error[ '%file' ];
        $error[ '%filename' ]   = \basename($error[ '%file' ]);
        $complaint              = '<strong>Complaint:</strong> <abbr title="@type">@short_type</abbr>: @message';
        $culprit                = '<br/><br/><strong>Culprit:</strong> %function at line %line of <abbr title="@file">%filename</abbr>.';
        $stacktrace              = '<br/><br/><strong>Stacktrace:</strong><pre>@backtrace_string</pre>';

        /**
         * Building beautiful message
         */
        $message = (string) new FormattableMarkup($complaint, $error);
        $message .= new FormattableMarkup($culprit, $error);
        $message .= new FormattableMarkup($stacktrace, $error);
        $content = \sprintf('<strong>Oh noooo! An unexpected Phpfastcache exception with <em>%s</em> driver occurred:</strong>', $culpritDriver);
        $content .= $message ? '</br></br><code>' . $message . '</code>' : '';

        /**
         * Building HTML page with our beautiful message
         */
        $html     = <<<HTML
<!DOCTYPE html>
<html>
<head>
<title>Unexpected Phpfastcache exception in {$culpritDriver} driver</title>
</head>
<body>{$content}</body>
</html>
HTML;
        $response = new Response($html);
        $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR, 'Service unavailable (with message)');
        $response->headers->add(
          [
            'Content-Type' => 'text/html',
          ]
        );
        $event->setResponse($response);

      }
    }
    /**
     * Else, we let
     * \Drupal\Core\EventSubscriber\FinalExceptionSubscriber
     * handle this exception
     */
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ KernelEvents::EXCEPTION ][] = ['onPhpfastcacheException', 255];
    return $events;
  }
}
