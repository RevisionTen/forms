<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ItemType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['formClass']) {
            $builder->add('data', $options['formClass'], [
                'label' => false,
                'items' => $options['items'],
            ]);
        }
        $builder->add('save', SubmitType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'formClass' => false,
            'items' => false,
        ]);
    }
}
