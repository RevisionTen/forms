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
     * FormSubmission constructor.
     *
     * @param FormRead  $formRead
     * @param string    $ip
     * @param \DateTime $expires
     * @param array     $payload
     */
    public function __construct(FormRead $formRead, string $ip, \DateTime $expires, array $payload)
    {
        $this->created = new \DateTime();
        $this->form = $formRead;
        $this->ip = $ip;
        $this->expires = $expires;
        $this->payload = json_encode($payload);
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
}
