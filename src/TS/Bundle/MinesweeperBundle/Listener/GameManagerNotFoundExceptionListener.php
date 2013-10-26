<?php

namespace TS\Bundle\MinesweeperBundle\Listener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use TS\Bundle\MinesweeperBundle\Exception\GameNotFoundException;

class GameManagerNotFoundExceptionListener
{
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if ($exception instanceof GameNotFoundException) {
            $response = new Response($exception->getMessage(), 404);

            $event->setResponse($response);
        }
    }
}
