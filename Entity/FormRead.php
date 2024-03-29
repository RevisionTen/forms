<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Entity;

use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class FormRead.
 *
 * This entity is a representation of the form aggregate as it exists in the event stream.
 * The purpose of this class is to make the aggregate accessible to EasyAdmin.
 *
 * @ORM\Entity
 * @ORM\Table(name="form_read", uniqueConstraints={
 *      @ORM\UniqueConstraint(name="unique_forms",
 *          columns={"uuid"})
 * })
 */
class FormRead
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private ?int $id =null;

    /**
     * @ORM\Column(type="string")
     */
    private ?string $uuid = null;

    /**
     * @ORM\Column(type="integer")
     */
    private ?int $version = null;

    /**
     * TODO: Switch to json once https://github.com/doctrine/doctrine2/pull/6988 is fixed.
     *
     * @var array|string|null
     *
     * @ORM\Column(type="text")
     */
    private $payload = null;

    /**
     * @var string|null
     * @ORM\Column(type="string")
     */
    private ?string $title = null;

    /**
     * @var string|null
     * @ORM\Column(type="string")
     */
    private ?string $email = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $emailCC = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $emailBCC = null;

    /**
     * @var string|null
     * @ORM\Column(type="string")
     */
    private ?string $sender = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $template = null;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $emailTemplate = null;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $emailTemplateCopy = null;

    /**
     * @var bool|null
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $html = null;

    /**
     * @var bool|null
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $deleted = null;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $successText = null;

    /**
     * @var bool|null
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $saveSubmissions = null;

    /**
     * @var bool|null
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $trackSubmissions = null;

    /**
     * @var bool|null
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $disableCsrfProtection = null;

    /**
     * @var bool|null
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $scrollToSuccessText = null;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    private $created = null;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    private $modified = null;

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getTitle() ?: (string) $this->getId();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     *
     * @return FormRead
     */
    public function setUuid($uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @param int $version
     *
     * @return FormRead
     */
    public function setVersion($version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * TODO: simplify method once https://github.com/doctrine/doctrine2/pull/6988 is fixed.
     *
     * @return array
     */
    public function getPayload(): array
    {
        return \is_string($this->payload) ? json_decode($this->payload, true) : $this->payload;
    }

    /**
     * TODO: simplify method once https://github.com/doctrine/doctrine2/pull/6988 is fixed.
     *
     * @param array $payload
     *
     * @return FormRead
     */
    public function setPayload($payload): self
    {
        $this->payload = json_encode($payload);

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return FormRead
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return FormRead
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmailCC(): ?string
    {
        return $this->emailCC;
    }

    /**
     * @param string $emailCC
     *
     * @return FormRead
     */
    public function setEmailCC(string $emailCC = null): self
    {
        $this->emailCC = $emailCC;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmailBCC(): ?string
    {
        return $this->emailBCC;
    }

    /**
     * @param string $emailBCC
     *
     * @return FormRead
     */
    public function setEmailBCC(string $emailBCC = null): self
    {
        $this->emailBCC = $emailBCC;

        return $this;
    }

    /**
     * @return string
     */
    public function getSender(): string
    {
        return $this->sender;
    }

    /**
     * @param string $sender
     *
     * @return FormRead
     */
    public function setSender(string $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTemplate(): ?string
    {
        return $this->template;
    }

    /**
     * @param string|null $template
     *
     * @return FormRead
     */
    public function setTemplate($template = null): self
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmailTemplate(): ?string
    {
        return $this->emailTemplate;
    }

    /**
     * @param string $emailTemplate
     *
     * @return FormRead
     */
    public function setEmailTemplate(string $emailTemplate = null): self
    {
        $this->emailTemplate = $emailTemplate;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmailTemplateCopy(): ?string
    {
        return $this->emailTemplateCopy;
    }

    /**
     * @param string $emailTemplateCopy
     *
     * @return FormRead
     */
    public function setEmailTemplateCopy(string $emailTemplateCopy = null): self
    {
        $this->emailTemplateCopy = $emailTemplateCopy;

        return $this;
    }

    /**
     * @return bool
     */
    public function getHtml(): bool
    {
        return $this->html;
    }

    /**
     * @param bool $html
     *
     * @return FormRead
     */
    public function setHtml(bool $html): self
    {
        $this->html = $html;

        return $this;
    }

    /**
     * @return bool
     */
    public function getDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @param bool $deleted
     *
     * @return FormRead
     */
    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * @return string
     */
    public function getSuccessText(): string
    {
        return $this->successText;
    }

    /**
     * @param string $successText
     *
     * @return FormRead
     */
    public function setSuccessText(string $successText): self
    {
        $this->successText = $successText;

        return $this;
    }

    /**
     * @return bool
     */
    public function getSaveSubmissions(): bool
    {
        return (bool) $this->saveSubmissions;
    }

    /**
     * @param bool|null $saveSubmissions
     *
     * @return FormRead
     */
    public function setSaveSubmissions(?bool $saveSubmissions): self
    {
        $this->saveSubmissions = (bool) $saveSubmissions;

        return $this;
    }

    /**
     * @return bool
     */
    public function getTrackSubmissions(): bool
    {
        return (bool) $this->trackSubmissions;
    }

    /**
     * @param bool|null $trackSubmissions
     *
     * @return FormRead
     */
    public function setTrackSubmissions(?bool $trackSubmissions): self
    {
        $this->trackSubmissions = (bool) $trackSubmissions;

        return $this;
    }

    /**
     * @return bool
     */
    public function getDisableCsrfProtection(): bool
    {
        return (bool) $this->disableCsrfProtection;
    }

    /**
     * @param bool|null $disableCsrfProtection
     *
     * @return FormRead
     */
    public function setDisableCsrfProtection(?bool $disableCsrfProtection): self
    {
        $this->disableCsrfProtection = (bool) $disableCsrfProtection;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getScrollToSuccessText(): ?bool
    {
        return $this->scrollToSuccessText;
    }

    /**
     * @param bool|null $scrollToSuccessText
     *
     * @return FormRead
     */
    public function setScrollToSuccessText(?bool $scrollToSuccessText): self
    {
        $this->scrollToSuccessText = (bool) $scrollToSuccessText;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @param DateTimeImmutable $created
     *
     * @return FormRead
     */
    public function setCreated(DateTimeImmutable $created): self
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getModified(): DateTime
    {
        return $this->modified;
    }

    /**
     * @param DateTimeImmutable $modified
     *
     * @return FormRead
     */
    public function setModified(DateTimeImmutable $modified): self
    {
        $this->modified = $modified;

        return $this;
    }
}
