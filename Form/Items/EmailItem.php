<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Form\Items;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class EmailItem extends TextItem
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->remove('isFirstname');
        $builder->remove('isName');

        $builder->add('replyTo', CheckboxType::class, [
            'label' => 'forms.label.replyTo',
            'translation_domain' => 'cms',
            'required' => false,
        ]);

        $builder->add('isReceiver', CheckboxType::class, [
            'label' => 'forms.label.isReceiver',
            'translation_domain' => 'cms',
            'required' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildItem(FormBuilderInterface $builder, array $item)
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
            'required' => $item['required'],
            'attr' => $attributes,
        ];

        if (isset($item['required']) && $item['required']) {
            $options['constraints'] = new NotBlank();
        }

        $builder->add($item['name'], EmailType::class, $options);
    }
}
