<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Interfaces;

use RevisionTen\Forms\Model\FormRead;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;

interface ItemInterface extends FormTypeInterface
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $item
     *
     * @return mixed
     */
    public static function getItem(FormBuilderInterface $builder, array $item);

    /**
     * @param array $item
     *
     * @return array
     */
    public static function getVariables(array $item): array;

    /**
     * This method is called when the items form data is used as an email.
     *
     * @param mixed $itemData
     *
     * @return string|null
     */
    public static function getEmail($itemData): ?string;

    /**
     * This method is called when the form is submitted after it was validated.
     * If this method returns false then the submission will be stopped.
     *
     * @param array         $data     the forms submitted data
     * @param array         $item     the item
     * @param FormRead      $formRead the forms read entity
     * @param FormInterface $form     the form
     *
     * @return bool
     */
    public function onSubmit(array $data, array $item, FormRead $formRead = null, FormInterface $form): bool;

    /**
     * This method is called when the form is submitted.
     * If this method returns false then the submission will be stopped.
     *
     * @param array         $data     the forms submitted data
     * @param array         $item     the item
     * @param FormRead      $formRead the forms read entity
     * @param FormInterface $form     the form
     *
     * @return bool
     */
    public function onValidate(array $data, array $item, FormRead $formRead = null, FormInterface $form): bool;
}
