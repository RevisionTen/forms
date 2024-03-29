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
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add('placeholder', TextType::class, [
            'label' => 'forms.label.placeholder',
            'translation_domain' => 'cms',
            'required' => false,
        ]);

        $builder->add('min', NumberType::class, [
            'label' => 'forms.label.min',
            'translation_domain' => 'cms',
            'required' => false,
        ]);

        $builder->add('max', NumberType::class, [
            'label' => 'forms.label.max',
            'translation_domain' => 'cms',
            'required' => false,
        ]);

        $builder->add('isSubject', CheckboxType::class, [
            'label' => 'forms.label.isSubject',
            'translation_domain' => 'cms',
            'required' => false,
        ]);

        $builder->add('isFirstname', CheckboxType::class, [
            'label' => 'forms.label.isFirstname',
            'translation_domain' => 'cms',
            'required' => false,
        ]);

        $builder->add('isName', CheckboxType::class, [
            'label' => 'forms.label.isName',
            'translation_domain' => 'cms',
            'required' => false,
        ]);
    }

    public function buildItem(FormBuilderInterface $builder, array $item): void
    {
        $attributes = [
            'aria-label' => $item['label'],
        ];

        if (isset($item['read_only']) && $item['read_only']) {
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
            'label' => !empty($item['hideLabel']) && $item['hideLabel'] ? false : $item['label'],
            'required' => $item['required'] ?? false,
            'attr' => $attributes,
            'constraints' => [],
            'label_html' => true,
        ];

        if (isset($item['required']) && $item['required']) {
            $options['constraints'][] = new NotBlank();
        }

        if ((isset($item['min']) && $item['min']) || (isset($item['max']) && $item['max'])) {
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
