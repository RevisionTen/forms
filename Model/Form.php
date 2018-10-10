<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Model;

use RevisionTen\CQRS\Model\Aggregate;

final class Form extends Aggregate
{
    /** @var string */
    public $title;

    /** @var string */
    public $email;

    /** @var string */
    public $emailCC;

    /** @var string */
    public $emailBCC;

    /** @var string */
    public $sender;

    /** @var integer */
    public $timelimit;

    /** @var string */
    public $timeLimitMessage;

    /** @var string */
    public $template;

    /** @var string */
    public $emailTemplate;

    /** @var string */
    public $emailTemplateCopy;

    /** @var bool */
    public $html;

    /** @var bool */
    public $deleted = false;

    /** @var string */
    public $successText;

    /** @var array */
    public $items;
}
