<?php

namespace RevisionTen\Forms;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FormsBundle extends Bundle
{
    public const VERSION = '3.0.0-dev';

    /**
     * {@inheritdoc}
     */
    public function boot(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
    }
}
