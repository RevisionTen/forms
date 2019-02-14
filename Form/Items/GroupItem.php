<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Form\Items;

use Symfony\Component\Form\FormBuilderInterface;

class GroupItem extends Item
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->remove('hideLabel');
        $builder->remove('required');
        $builder->remove('popover');
    }
}
