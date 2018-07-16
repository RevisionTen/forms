<?php

namespace RevisionTen\Forms\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FormsExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);
        $container->setParameter('forms', $config);
    }

    public function prepend(ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('config.yaml');

        $configs = $container->getExtensionConfig('forms');

        $loadEasyAdminConfig = true;
        foreach ($configs as $config) {
            if (isset($config['load_easyadmin_config'])) {
                $loadEasyAdminConfig = $config['load_easyadmin_config'];
                break;
            }
        }

        $configIsLoaded = false;
        if ($loadEasyAdminConfig) {
            // Load EasyAdmin configuration.
            $configIsLoaded = true;
            $loader->load('easyadmin.yml');
        }

        if (empty($config)) {
            // Load default forms bundle config.
            $loader->load('forms.yaml');
            if (!$configIsLoaded) {
                $loader->load('easyadmin.yml');
            }
        }
    }
}
