<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Form\Items;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CheckboxItem extends Item
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->remove('hideLabel');
        $builder->remove('popover');
    }

    public function buildItem(FormBuilderInterface $builder, array $item): void
    {
        $attributes = [
            'aria-label' => $item['label'],
        ];

        if (isset($item['read_only']) && $item['read_only']) {
            $attributes['readonly'] = true;
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
            'label_attr' => [
                'class' => 'checkbox-custom',
            ],
            'label_html' => true,
        ];

        if (isset($item['required']) && $item['required']) {
            $options['constraints'] = new NotBlank();
        }

        $builder->add($item['name'], CheckboxType::class, $options);
    }

    public static function getVariables(array $item): array
    {
        return [$item['name']." ? 'Yes' : 'No'"];
    }
}
