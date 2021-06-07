<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Model;

use RevisionTen\CQRS\Model\Aggregate;

final class Form extends Aggregate
{
    public ?string $title = null;

    public ?string $email = null;

    public ?string $emailCC = null;

    public ?string $emailBCC = null;

    public ?string $sender = null;

    public ?int $timelimit = null;

    public ?string $timeLimitMessage = null;

    public ?string $template = null;

    public ?string $emailTemplate = null;

    public ?string $emailTemplateCopy = null;

    public bool $html = false;

    public bool $deleted = false;

    public ?string $successText = null;

    public bool $saveSubmissions = false;

    public bool $trackSubmissions = false;

    public bool $disableCsrfProtection = false;

    public bool $scrollToSuccessText = false;

    public array $items = [];
}
