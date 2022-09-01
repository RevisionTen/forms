<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Form\Items;

use RevisionTen\CMS\Form\Types\CKEditorType;
use RevisionTen\Forms\Form\MarkupType;
use Symfony\Component\Form\FormBuilderInterface;

class MarkupItem extends Item
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->remove('read_only');
        $builder->remove('required');
        $builder->remove('hideLabel');

        $builder->add('markup', CKEditorType::class, [
            'label' => 'forms.label.markup',
            'translation_domain' => 'cms',
            'required' => true,
        ]);
    }

    public function buildItem(FormBuilderInterface $builder, array $item): void
    {
        $options = [
            'label' => false,
            'required' => false,
            'markup' => $item['markup'],
        ];

        $builder->add($item['name'], MarkupType::class, $options);
    }

    public static function getVariables(array $item): array
    {
        return [];
    }
}
