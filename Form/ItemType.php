<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['formClass']) {
            $builder->add('data', $options['formClass'], [
                'label' => false,
                'items' => $options['items'],
            ]);
        }
        $builder->add('save', SubmitType::class, [
            'label' => 'admin.btn.save',
            'translation_domain' => 'cms',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'formClass' => false,
            'items' => false,
        ]);
    }
}
