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
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('choices', TextareaType::class, [
            'label' => 'Choices',
            'required' => true,
            'attr' => [
                'rows' => 10,
                'placeholder' => 'A list of choices separated by line breaks. You can specify a key by separating it with a pipe char. key|label',
            ],
        ]);

        $builder->add('placeholder', TextType::class, [
            'label' => 'Placeholder',
            'required' => false,
        ]);

        $builder->add('expanded', CheckboxType::class, [
            'label' => 'Expanded',
            'required' => false,
        ]);

        $builder->add('multiple', CheckboxType::class, [
            'label' => 'Multiple',
            'required' => false,
        ]);

        $builder->add('isReceiver', CheckboxType::class, [
            'label' => 'Is Receiver',
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

        // TODO: array map.
        foreach ($choices as $key => $line) {
            unset($choices[$key]);

            if (false !== strpos($line, '|')) {
                $line = explode("|", $line);
                $key = $line[0];
                $label = $line[1];
            } else {
                $label = $line;
                $key = $line;
            }

            $choices[trim($label)] = trim($key);
        }

        $options = [
            'label' => $item['label'] ?? false,
            'required' => $item['required'] ?? false,
            'attr' => $attributes,
            'choices' => $choices,
            'expanded' => $item['expanded'] ?? false,
            'multiple' => $item['multiple'] ?? false,
            'placeholder' => $item['placeholder'] ?? $item['label'],
        ];

        if ($item['required']) {
            $options['constraints'] = new NotBlank();
        }

        $builder->add($item['name'], ChoiceType::class, $options);
    }

    /**
     * {@inheritdoc}
     */
    public static function getVariables(array $item): array
    {
        $var = $item['multiple'] ? $item['name']."|join(', ')" : $item['name'];

        return [$var];
    }
}
