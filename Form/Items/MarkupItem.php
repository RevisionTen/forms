<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Form\Items;

use RevisionTen\CMS\Form\Types\CKEditorType;
use RevisionTen\Forms\Form\MarkupType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class MarkupItem extends Item
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
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

    /**
     * {@inheritdoc}
     */
    public function buildItem(FormBuilderInterface $builder, array $item)
    {
        $options = [
            'label' => false,
            'required' => false,
            'markup' => $item['markup'],
        ];

        $builder->add($item['name'], MarkupType::class, $options);
    }

    /**
     * {@inheritdoc}
     */
    public static function getVariables(array $item): array
    {
        return [];
    }
}
