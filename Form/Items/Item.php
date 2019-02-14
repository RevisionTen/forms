<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Form\Items;

use RevisionTen\Forms\Interfaces\ItemInterface;
use RevisionTen\Forms\Model\FormRead;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class Item extends AbstractType implements ItemInterface
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'items' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('label', TextType::class, [
            'label' => 'Label',
            'required' => true,
        ]);

        $builder->add('name', TextType::class, [
            'label' => 'Name',
            'required' => true,
            'attr' => [
                'placeholder' => 'only use lowercase letters',
            ],
        ]);

        $builder->add('required', CheckboxType::class, [
            'label' => 'Required',
            'required' => false,
        ]);

        $builder->add('read_only', CheckboxType::class, [
            'label' => 'Read Only',
            'required' => false,
        ]);

        $builder->add('hideLabel', CheckboxType::class, [
            'label' => 'Hide Label',
            'required' => false,
        ]);

        if (class_exists('\Ivory\CKEditorBundle\Form\Type\CKEditorType')) {
            $textAreaType = \Ivory\CKEditorBundle\Form\Type\CKEditorType::class;
        } else {
            $textAreaType = TextareaType::class;
        }

        $builder->add('popover', $textAreaType, [
            'label' => 'Popover',
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
            'label' => !empty($item['hideLabel']) && $item['hideLabel'] ? false : $item['label'],
            'required' => $item['required'] ?? false,
            'attr' => $attributes,
        ];

        if ($item['required']) {
            $options['constraints'] = new NotBlank();
        }

        $builder->add($item['name'], TextType::class, $options);
    }

    /**
     * {@inheritdoc}
     */
    public static function getVariables(array $item): array
    {
        return [$item['name']];
    }

    /**
     * {@inheritdoc}
     */
    public function onSubmit(array $data, array $item, FormRead $formRead = null, FormInterface $form): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function onValidate(array $data, array $item, FormRead $formRead = null, FormInterface $form): bool
    {
        return true;
    }
}
