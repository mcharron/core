<?php

declare(strict_types = 1);

namespace Onyx\Traits;

use Onyx\Services\CQS\QueryBuses;
use Onyx\Services\CQS\CommandBuses;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

trait ControllerTestCaseRelated
{
    private
        $queryBus,
        $commandBus;

    private function initializeControllerForTest($controller)
    {
        $traits = class_uses($controller);

        if(in_array(TwigAware::class, $traits))
        {
            $fakeTwig = new class extends \Twig_Environment {
                public function __construct(){}
                public function render($name, array $context = array()){ return '';}
            };

            $controller->setTwig($fakeTwig);
        }

        if(in_array(BusAware::class, $traits) || in_array(QueryBusAware::class, $traits))
        {
            $this->queryBus = new QueryBuses\InMemory();
            $controller->setQueryBus($this->queryBus);
        }

        if(in_array(BusAware::class, $traits) || in_array(CommandBusAware::class, $traits))
        {
            $this->commandBus = new CommandBuses\InMemory();
            $controller->setCommandBus($this->commandBus);
        }

        if(in_array(UrlGeneratorAware::class, $traits))
        {
            $fakeGenerator = new class implements UrlGeneratorInterface {
                public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH) { return 'fake/route'; }
                public function setContext(RequestContext $context) {}
                public function getContext() {}
            };

            $controller->setUrlGenerator($fakeGenerator);
        }
    }
}