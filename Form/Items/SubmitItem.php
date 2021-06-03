<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Form\Items;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class SubmitItem extends Item
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->remove('read_only');
        $builder->remove('required');
        $builder->remove('hideLabel');
        $builder->remove('popover');
    }

    public function buildItem(FormBuilderInterface $builder, array $item)
    {
        $builder->add($item['name'], SubmitType::class, [
            'label' => $item['label'],
        ]);
    }

    public static function getVariables(array $item): array
    {
        return [];
    }
}
