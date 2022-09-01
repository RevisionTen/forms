<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Form\Items;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChoiceItem extends Item
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add('choices', TextareaType::class, [
            'label' => 'forms.label.choices',
            'translation_domain' => 'cms',
            'required' => true,
            'help' => 'forms.help.choices',
            'attr' => [
                'rows' => 10,
            ],
        ]);

        $builder->add('placeholder', TextType::class, [
            'label' => 'forms.label.placeholder',
            'translation_domain' => 'cms',
            'required' => false,
        ]);

        $builder->add('expanded', CheckboxType::class, [
            'label' => 'forms.label.expanded',
            'translation_domain' => 'cms',
            'required' => false,
        ]);

        $builder->add('multiple', CheckboxType::class, [
            'label' => 'forms.label.multiple',
            'translation_domain' => 'cms',
            'required' => false,
        ]);

        $builder->add('isReceiver', CheckboxType::class, [
            'label' => 'forms.label.isReceiver',
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

        $choices = explode("\n", $item['choices']);
        $parsedChoices = [];
        foreach ($choices as $key => $line) {
            if (false !== strpos($line, '|')) {
                [$key, $label] = explode('|', $line);
            } else {
                $label = $line;
                $key = $line;
            }
            $parsedChoices[trim((string) $label)] = trim((string) $key);
        }

        $options = [
            'label' => !empty($item['hideLabel']) && $item['hideLabel'] ? false : $item['label'],
            'required' => $item['required'] ?? false,
            'attr' => $attributes,
            'choices' => $parsedChoices,
            'expanded' => $item['expanded'] ?? false,
            'multiple' => $item['multiple'] ?? false,
            'placeholder' => $item['placeholder'] ?? $item['label'],
            'label_html' => true,
        ];

        // Make the emails unreadable in the frontend.
        if (isset($item['isReceiver']) && $item['isReceiver']) {
            $options['choice_value'] = static function ($value) {
                return !empty($value) ? md5($value) : null;
            };
        }

        if ($item['expanded']) {
            $options['label_attr'] = [
                'class' => ($item['multiple'] ? 'checkbox-custom' : 'radio-custom'),
            ];
        } elseif (!$item['multiple'])  {
            $options['attr']['class'] = 'custom-select '.($options['attr']['class'] ?? '');
        }

        if (isset($item['required']) && $item['required']) {
            $options['constraints'] = new NotBlank();
        }

        $builder->add($item['name'], ChoiceType::class, $options);
    }

    public static function getVariables(array $item): array
    {
        $var = $item['multiple'] ? $item['name']."|join(', ')" : $item['name'];

        return [$var];
    }
}
