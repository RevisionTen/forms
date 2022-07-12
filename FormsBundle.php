<?php

namespace RevisionTen\Forms;

use RevisionTen\Forms\Entity\FormRead;
use RevisionTen\Forms\Entity\FormSubmission;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class FormsBundle extends Bundle
{
    public const VERSION = '3.0.4';
}

class_alias(FormRead::class, '\\RevisionTen\\Forms\\Model\\FormRead');
class_alias(FormSubmission::class, '\\RevisionTen\\Forms\\Model\\FormSubmission');
