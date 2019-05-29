<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class FormSubmission.
 *
 * @ORM\Entity
 */
class FormSubmission
{
    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    private $toEmail;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    private $ccEmail;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    private $bccEmail;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $ip;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    private $expires;

    /**
     * @var FormRead
     * @ORM\ManyToOne(targetEntity="\RevisionTen\Forms\Model\FormRead")
     * @ORM\JoinColumn(name="form_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $form;

    /**
     * TODO: Switch to json once https://github.com/doctrine/doctrine2/pull/6988 is fixed.
     *
     * @var array
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $payload;

    /**
     * @var bool|null
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $opened;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    private $trackingToken;

    /**
     * FormSubmission constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        $this->created = new \DateTime();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return FormSubmission
     */
    public function setId(int $id): FormSubmission
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getToEmail(): ?string
    {
        return $this->toEmail;
    }

    /**
     * @param string|null $toEmail
     *
     * @return FormSubmission
     */
    public function setToEmail(?string $toEmail): FormSubmission
    {
        $this->toEmail = $toEmail;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCcEmail(): ?string
    {
        return $this->ccEmail;
    }

    /**
     * @param string|null $ccEmail
     *
     * @return FormSubmission
     */
    public function setCcEmail(?string $ccEmail): FormSubmission
    {
        $this->ccEmail = $ccEmail;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBccEmail(): ?string
    {
        return $this->bccEmail;
    }

    /**
     * @param string|null $bccEmail
     *
     * @return FormSubmission
     */
    public function setBccEmail(?string $bccEmail): FormSubmission
    {
        $this->bccEmail = $bccEmail;

        return $this;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     *
     * @return FormSubmission
     */
    public function setIp(string $ip): FormSubmission
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreated(): \DateTime
    {
        return $this->created;
    }

    /**
     * @param \DateTime $created
     *
     * @return FormSubmission
     */
    public function setCreated(\DateTime $created): FormSubmission
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getExpires(): \DateTime
    {
        return $this->expires;
    }

    /**
     * @param \DateTime $expires
     *
     * @return FormSubmission
     */
    public function setExpires(\DateTime $expires): FormSubmission
    {
        $this->expires = $expires;

        return $this;
    }

    /**
     * @return FormRead
     */
    public function getForm(): FormRead
    {
        return $this->form;
    }

    /**
     * @param FormRead $form
     *
     * @return FormSubmission
     */
    public function setForm(FormRead $form): FormSubmission
    {
        $this->form = $form;

        return $this;
    }

    /**
     * TODO: simplify method once https://github.com/doctrine/doctrine2/pull/6988 is fixed.
     *
     * @return array|null
     */
    public function getPayload(): ?array
    {
        return \is_string($this->payload) ? json_decode($this->payload, true) : $this->payload;
    }

    /**
     * TODO: simplify method once https://github.com/doctrine/doctrine2/pull/6988 is fixed.
     *
     * @param array $payload
     *
     * @return FormSubmission
     */
    public function setPayload($payload): self
    {
        $this->payload = json_encode($payload);

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getOpened(): ?bool
    {
        return $this->opened;
    }

    /**
     * @param bool|null $opened
     *
     * @return FormSubmission
     */
    public function setOpened(?bool $opened): FormSubmission
    {
        $this->opened = $opened;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTrackingToken(): ?string
    {
        return $this->trackingToken;
    }

    /**
     * @param string|null $trackingToken
     *
     * @return FormSubmission
     */
    public function setTrackingToken(?string $trackingToken): FormSubmission
    {
        $this->trackingToken = $trackingToken;

        return $this;
    }
}
