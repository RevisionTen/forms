<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Form\Items;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class EntityItem extends Item
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $entityClasses = [];
        $metaData = $this->entityManager->getMetadataFactory()->getAllMetadata();

        /**
         * @var ClassMetadata $entityMetaData
         */
        foreach ($metaData as $entityMetaData) {
            $namespace = $entityMetaData->namespace;
            $isAppNamespace = 'AppBundle\Entity' === $namespace || 'App\Entity' === $namespace;
            $isNotComponent = !strpos($entityMetaData->getName(), 'Component');
            if ($isAppNamespace && $isNotComponent && $entityMetaData->hasField('title')) {
                $className = str_replace($namespace.'\\', '', $entityMetaData->getName());
                $entityClasses[$className] = $entityMetaData->getName();
            }
        }

        $builder->add('entity_class', ChoiceType::class, [
            'label' => 'forms.label.entity_class',
            'translation_domain' => 'cms',
            'choices' => $entityClasses,
            'required' => true,
            'constraints' => new NotBlank(),
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
    }

    public function buildItem(FormBuilderInterface $builder, array $item)
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

        $entityClass = $item['entity_class'];

        $options = [
            'label' => !empty($item['hideLabel']) && $item['hideLabel'] ? false : $item['label'],
            'required' => $item['required'],
            'attr' => $attributes,
            'expanded' => $item['expanded'] ?? false,
            'multiple' => $item['multiple'] ?? false,
            'class' => $entityClass,
            'choice_label' => $item['choice_label'] ?? 'title',
            'placeholder' => $item['placeholder'] ?? $item['label'],
        ];

        if (isset($item['required']) && $item['required']) {
            $options['constraints'] = new NotBlank();
        }

        $builder->add($item['name'], EntityType::class, $options);
    }

    public static function getVariables(array $item): array
    {
        $var = $item['multiple'] ? $item['name']."|join(', ')" : $item['name'];

        return [$var];
    }
}
