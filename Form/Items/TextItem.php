<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Form\Items;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class TextItem extends Item
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('placeholder', TextType::class, [
            'label' => 'Placeholder',
            'required' => false,
        ]);

        $builder->add('min', NumberType::class, [
            'label' => 'Min Length',
            'required' => false,
        ]);

        $builder->add('max', NumberType::class, [
            'label' => 'Max Length',
            'required' => false,
        ]);

        $builder->add('isSubject', CheckboxType::class, [
            'label' => 'Is Subject',
            'required' => false,
        ]);

        $builder->add('isFirstname', CheckboxType::class, [
            'label' => 'Is Firstname',
            'required' => false,
        ]);

        $builder->add('isName', CheckboxType::class, [
            'label' => 'Is Name',
            'required' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getItem(FormBuilderInterface $builder, array $item)
    {
        $attributes = [];

        if ($item['read_only']) {
            $attributes['readonly'] = true;
        }

        if (isset($item['placeholder']) && $item['placeholder']) {
            $attributes['placeholder'] = $item['placeholder'];
        }

        if (isset($item['twig_variable']) && $item['twig_variable']) {
            $attributes['twig_variable'] = $item['twig_variable'];
        }

        if (isset($item['popover']) && trim($item['popover'])) {
            $attributes['data-toggle'] = 'popover';
            $attributes['data-placement'] = 'top';
            $attributes['data-trigger'] = 'focus';
            $attributes['data-html'] = 'true';
            $attributes['data-content'] = $item['popover'];
        }

        $options = [
            'label' => $item['label'],
            'required' => $item['required'] ?? false,
            'attr' => $attributes,
            'constraints' => [],
        ];

        if ($item['required']) {
            $options['constraints'][] = new NotBlank();
        }

        if ((isset($item['min']) && $item['min']) || isset($item['max']) && $item['max']) {
            $min = $item['min'] ?? 0;
            $max = $item['max'] ?? null;
            $options['constraints'][] = new Length([
                'min' => $min,
                'max' => $max,
            ]);
        }

        $builder->add($item['name'], TextType::class, $options);
    }
}
