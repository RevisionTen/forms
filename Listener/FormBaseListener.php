<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Listener;

use RevisionTen\Forms\Services\FormService;

abstract class FormBaseListener
{
    /**
     * @var FormService
     */
    protected $formService;

    /**
     * FormBaseListener constructor.
     *
     * @param FormService $formService
     */
    public function __construct(FormService $formService)
    {
        $this->formService = $formService;
    }
}
