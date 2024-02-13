<?php


namespace HalloVerden\RouteDeprecationBundle;


use HalloVerden\RouteDeprecationBundle\EventListener\DeprecatedRouteListener;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

class HalloVerdenRouteDeprecationBundle extends AbstractBundle {

  public function configure(DefinitionConfigurator $definition): void {
    $definition->rootNode()
      ->addDefaultsIfNotSet()
      ->children()
        ->arrayNode('deprecation')
          ->addDefaultsIfNotSet()
          ->children()
            ->scalarNode('dateTimeFormat')->defaultValue(DeprecatedRouteListener::DEFAULT_DEPRECATION_DATE_TIME_FORMAT)->end()
            ->scalarNode('link')->defaultNull()->end()
          ->end()
        ->end()
        ->arrayNode('sunset')
          ->addDefaultsIfNotSet()
          ->children()
            ->scalarNode('dateTimeFormat')->defaultValue(DeprecatedRouteListener::DEFAULT_SUNSET_DATE_TIME_FORMAT)->end()
            ->scalarNode('link')->defaultNull()->end()
          ->end()
        ->end()
      ->end()
    ;
  }

  public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void {
    $container->services()
      ->set('hallo_verden.route_deprecation.event_listener.route_deprecation', DeprecatedRouteListener::class)
        ->args([
          service('logger')->nullOnInvalid(),
          $config['deprecation']['dateTimeFormat'],
          $config['sunset']['dateTimeFormat'],
          $config['deprecation']['link'],
          $config['sunset']['link'],
          service('clock')->nullOnInvalid()
        ])
        ->tag('kernel.event_subscriber');
  }

}
