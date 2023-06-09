<?php

namespace ContainerJArwi4l;

use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

/**
 * @internal This class has been auto-generated by the Symfony Dependency Injection Component.
 */
class get_Security_RequestMatcher_I1dTZBKService extends App_KernelDevDebugContainer
{
    /**
     * Gets the private '.security.request_matcher.I1dTZBK' shared service.
     *
     * @return \Symfony\Component\HttpFoundation\ChainRequestMatcher
     */
    public static function do($container, $lazyLoad = true)
    {
        include_once \dirname(__DIR__, 4).'/vendor/symfony/http-foundation/RequestMatcherInterface.php';
        include_once \dirname(__DIR__, 4).'/vendor/symfony/http-foundation/ChainRequestMatcher.php';
        include_once \dirname(__DIR__, 4).'/vendor/symfony/http-foundation/RequestMatcher/PathRequestMatcher.php';

        return $container->privates['.security.request_matcher.I1dTZBK'] = new \Symfony\Component\HttpFoundation\ChainRequestMatcher([0 => new \Symfony\Component\HttpFoundation\RequestMatcher\PathRequestMatcher('^/api/login_check')]);
    }
}
