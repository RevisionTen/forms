<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Form\Items;

use RevisionTen\CMS\Form\Types\CKEditorType;
use RevisionTen\Forms\Interfaces\ItemInterface;
use RevisionTen\Forms\Model\FormRead;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class Item extends AbstractType implements ItemInterface
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'items' => false,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', TextType::class, [
            'label' => 'forms.label.label',
            'translation_domain' => 'cms',
            'required' => true,
            'constraints' => new NotBlank(),
        ]);

        $builder->add('name', TextType::class, [
            'label' => 'forms.label.name',
            'translation_domain' => 'cms',
            'required' => true,
            'constraints' => new NotBlank(),
            'attr' => [
                'placeholder' => 'forms.placeholder.name',
            ],
        ]);

        $builder->add('required', CheckboxType::class, [
            'label' => 'forms.label.required',
            'translation_domain' => 'cms',
            'required' => false,
        ]);

        $builder->add('read_only', CheckboxType::class, [
            'label' => 'forms.label.read_only',
            'translation_domain' => 'cms',
            'required' => false,
        ]);

        $builder->add('hideLabel', CheckboxType::class, [
            'label' => 'forms.label.hideLabel',
            'translation_domain' => 'cms',
            'required' => false,
        ]);

        $builder->add('popover', CKEditorType::class, [
            'label' => 'forms.label.popover',
            'translation_domain' => 'cms',
            'required' => false,
        ]);
    }

    public function buildItem(FormBuilderInterface $builder, array $itemOptions): void
    {
        self::getItem($builder, $itemOptions);
    }

    public static function getItem(FormBuilderInterface $builder, array $item)
    {
        $attributes = [
            'aria-label' => $item['label'],
        ];

        if (!empty($item['read_only'])) {
            $attributes['readonly'] = true;
        }

        if (!empty($item['placeholder'])) {
            $attributes['placeholder'] = $item['placeholder'];
        }

        if (!empty($item['twig_variable'])) {
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
            'label_html' => true,
        ];

        if (isset($item['required']) && $item['required']) {
            $options['constraints'] = new NotBlank();
        }

        $builder->add($item['name'], TextType::class, $options);
    }

    public static function getVariables(array $item): array
    {
        return [$item['name']];
    }

    public static function getEmail($itemData): ?string
    {
        return $itemData;
    }

    public function onSubmit(array &$data, array $item, ?FormRead $formRead, FormInterface $form): bool
    {
        return true;
    }

    public function onValidate(array &$data, array $item, ?FormRead $formRead, FormInterface $form): bool
    {
        return true;
    }
}
