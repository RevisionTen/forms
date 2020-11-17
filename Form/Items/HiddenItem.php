<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Form\Items;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class HiddenItem extends Item
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('isSubject', CheckboxType::class, [
            'label' => 'Is Subject',
            'required' => false,
        ]);

        $builder->add('isReceiver', CheckboxType::class, [
            'label' => 'Is Receiver',
            'required' => false,
        ]);

        $builder->remove('hideLabel');
        $builder->remove('popover');
    }

    /**
     * {@inheritdoc}
     */
    public function buildItem(FormBuilderInterface $builder, array $item)
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
