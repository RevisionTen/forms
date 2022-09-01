<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Form\Items;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class HiddenItem extends Item
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add('isSubject', CheckboxType::class, [
            'label' => 'forms.label.isSubject',
            'translation_domain' => 'cms',
            'required' => false,
        ]);

        $builder->add('isReceiver', CheckboxType::class, [
            'label' => 'forms.label.isReceiver',
            'translation_domain' => 'cms',
            'required' => false,
        ]);

        $builder->remove('hideLabel');
        $builder->remove('popover');
    }

    public function buildItem(FormBuilderInterface $builder, array $item): void
    {
        $attributes = [];

        if (isset($item['twig_variable']) && $item['twig_variable']) {
            $attributes['twig_variable'] = $item['twig_variable'];
        }

        $options = [
            'label' => false,
            'required' => $item['required'] ?? false,
            'attr' => $attributes,
            'constraints' => [],
        ];

        if (isset($item['required']) && $item['required']) {
            $options['constraints'][] = new NotBlank();
        }

        if (isset($item['read_only']) && $item['read_only']) {
            // Do not add the element add all. Default data remains unchanged.
        } else {
            $builder->add($item['name'], HiddenType::class, $options);
        }
    }
}
