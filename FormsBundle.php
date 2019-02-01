<?php

namespace RevisionTen\Forms;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FormsBundle extends Bundle
{
    public const VERSION = '1.0.4';

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
    }
}
