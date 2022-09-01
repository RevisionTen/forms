<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Form\Items;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class SubmitItem extends Item
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->remove('read_only');
        $builder->remove('required');
        $builder->remove('hideLabel');
        $builder->remove('popover');
    }

    public function buildItem(FormBuilderInterface $builder, array $item): void
    {
        $builder->add($item['name'], SubmitType::class, [
            'label' => $item['label'],
            'label_html' => true,
        ]);
    }

    public static function getVariables(array $item): array
    {
        return [];
    }
}
