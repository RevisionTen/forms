<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Form\Items;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class TextAreaItem extends TextItem
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->remove('isFirstname');
        $builder->remove('isName');
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

        if ($item['placeholder']) {
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
            'required' => $item['required'],
            'attr' => $attributes,
        ];

        if ($item['required']) {
            $options['constraints'] = new NotBlank();
        }

        $builder->add($item['name'], TextareaType::class, $options);
    }
}
