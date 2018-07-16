<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Form\Items;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class SubmitItem extends Item
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
    }

    /**
     * {@inheritdoc}
     */
    public static function getItem(FormBuilderInterface $builder, array $item)
    {
        $builder->add($item['name'], SubmitType::class, [
            'label' => $item['label'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getVariables(array $item): array
    {
        return [];
    }
}
