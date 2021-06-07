<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Form;

use RevisionTen\CMS\Form\Types\CKEditorType;
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
            'label' => 'admin.label.title',
            'translation_domain' => 'cms',
        ]);

        $builder->add('email', TextType::class, [
            'label' => 'admin.label.toEmail',
            'translation_domain' => 'cms',
            'attr' => [
                'placeholder' => 'forms.placeholder.emails',
            ],
        ]);

        $builder->add('emailCC', TextType::class, [
            'label' => 'admin.label.ccEmail',
            'translation_domain' => 'cms',
            'required' => false,
            'attr' => [
                'placeholder' => 'forms.placeholder.emails',
            ],
        ]);

        $builder->add('emailBCC', TextType::class, [
            'label' => 'admin.label.bccEmail',
            'translation_domain' => 'cms',
            'required' => false,
            'attr' => [
                'placeholder' => 'forms.placeholder.emails',
            ],
        ]);

        $builder->add('sender', TextType::class, [
            'label' => 'forms.label.sender',
            'translation_domain' => 'cms',
        ]);

        $builder->add('timelimit', NumberType::class, [
            'label' => 'forms.label.timelimit',
            'translation_domain' => 'cms',
            'required' => false,
            'attr' => [
                'placeholder' => 'forms.placeholder.timelimit',
            ],
        ]);

        $builder->add('timeLimitMessage', TextareaType::class, [
            'label' => 'forms.label.timeLimitMessage',
            'translation_domain' => 'cms',
            'required' => false,
            'attr' => [
                'rows' => 3,
            ],
        ]);

        $builder->add('emailTemplate', TextareaType::class, [
            'label' => 'forms.label.emailTemplate',
            'translation_domain' => 'cms',
            'required' => true,
            'attr' => [
                'rows' => 10,
            ],
        ]);

        $builder->add('emailTemplateCopy', TextareaType::class, [
            'label' => 'forms.label.emailTemplateCopy',
            'translation_domain' => 'cms',
            'required' => false,
            'attr' => [
                'rows' => 10,
            ],
        ]);

        $builder->add('html', CheckboxType::class, [
            'label' => 'forms.label.html',
            'translation_domain' => 'cms',
            'required' => false,
        ]);


        $builder->add('successText', CKEditorType::class, [
            'label' => 'forms.label.successText',
            'translation_domain' => 'cms',
            'required' => true,
        ]);

        $builder->add('template', TextType::class, [
            'label' => 'forms.label.template',
            'translation_domain' => 'cms',
            'required' => false,
        ]);

        $builder->add('saveSubmissions', CheckboxType::class, [
            'label' => 'forms.label.saveSubmissions',
            'translation_domain' => 'cms',
            'required' => false,
        ]);

        $builder->add('trackSubmissions', CheckboxType::class, [
            'label' => 'forms.label.trackSubmissions',
            'translation_domain' => 'cms',
            'required' => false,
        ]);

        $builder->add('disableCsrfProtection', CheckboxType::class, [
            'label' => 'forms.label.disableCsrfProtection',
            'translation_domain' => 'cms',
            'required' => false,
        ]);

        $builder->add('scrollToSuccessText', CheckboxType::class, [
            'label' => 'forms.label.scrollToSuccessText',
            'translation_domain' => 'cms',
            'required' => false,
        ]);

        $builder->add('save', SubmitType::class, [
            'label' => 'admin.btn.save',
            'translation_domain' => 'cms',
        ]);
    }
}
