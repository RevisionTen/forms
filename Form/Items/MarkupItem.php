<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Form\Items;

use RevisionTen\Forms\Form\MarkupType;
use Ivory\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\FormBuilderInterface;

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

        $builder->add('markup', CKEditorType::class, [
            'label' => 'Markup',
            'required' => true,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getItem(FormBuilderInterface $builder, array $item)
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
