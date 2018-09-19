<?php

namespace RevisionTen\Forms\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FormsExtension extends Extension implements PrependExtensionInterface
{
    /**
     * Merge all forms configs in reverse order.
     * First (user defined) config is most important.
     *
     * @param array $configs
     *
     * @return array
     */
    private static function mergeFormsConfig(array $configs): array
    {
        $configs = array_reverse($configs);
        $config = [];
        foreach ($configs as $subConfig) {
            $config = array_merge($config, $subConfig);
        }

        return $config;
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $config = self::mergeFormsConfig($configs);

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

        if ($loadEasyAdminConfig) {
            // Load EasyAdmin configuration.
            $loader->load('easyadmin.yml');
        }

        if (empty($configs)) {
            // Load default forms bundle config.
            $loader->load('forms.yaml');
        }
    }
}
