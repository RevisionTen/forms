<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class FormType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, [
            'label' => 'Title',
            'attr' => [
                'placeholder' => 'Title',
            ],
        ]);

        $builder->add('email', TextType::class, [
            'label' => 'Receiver',
            'attr' => [
                'placeholder' => 'A comma separated list of emails',
            ],
        ]);

        $builder->add('emailCC', TextType::class, [
            'label' => 'CC',
            'required' => false,
            'attr' => [
                'placeholder' => 'A comma separated list of emails',
            ],
        ]);

        $builder->add('emailBCC', TextType::class, [
            'label' => 'BCC',
            'required' => false,
            'attr' => [
                'placeholder' => 'A comma separated list of emails',
            ],
        ]);

        $builder->add('sender', TextType::class, [
            'label' => 'Sender',
            'attr' => [
                'placeholder' => 'Sender',
            ],
        ]);

        $builder->add('timelimit', NumberType::class, [
            'label' => 'Timelimit',
            'required' => false,
            'attr' => [
                'placeholder' => 'Timelimit (in seconds)',
            ],
        ]);

        $builder->add('timeLimitMessage', TextareaType::class, [
            'label' => 'Timelimit Message',
            'required' => false,
            'attr' => [
                'rows' => 3,
                'placeholder' => 'Timelimit Message',
            ],
        ]);

        $builder->add('emailTemplate', TextareaType::class, [
            'label' => 'Email Template',
            'required' => true,
            'attr' => [
                'rows' => 10,
                'placeholder' => 'Email Template',
            ],
        ]);

        $builder->add('emailTemplateCopy', TextareaType::class, [
            'label' => 'Email Template CC/BCC',
            'required' => false,
            'attr' => [
                'rows' => 10,
                'placeholder' => 'Email Template CC/BCC',
            ],
        ]);

        $builder->add('html', CheckboxType::class, [
            'label' => 'Send as HTML',
            'required' => false,
        ]);

        if (class_exists('\Ivory\CKEditorBundle\Form\Type\CKEditorType')) {
            $textAreaType = \Ivory\CKEditorBundle\Form\Type\CKEditorType::class;
        } else {
            $textAreaType = TextareaType::class;
        }

        $builder->add('successText', $textAreaType, [
            'label' => 'Success Text',
            'required' => true,
            'attr' => [
                'rows' => 10,
                'placeholder' => 'Success Text',
            ],
        ]);

        $builder->add('template', TextType::class, [
            'label' => 'Template',
            'required' => false,
            'attr' => [
                'placeholder' => 'Twig Template Path',
            ],
        ]);

        $builder->add('save', SubmitType::class, [
            'label' => 'Save',
        ]);
    }
}
