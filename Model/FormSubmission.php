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
     * FormSubmission constructor.
     */
    public function __construct(FormRead $formRead, string $ip, \DateTime $expires)
    {
        $this->created = new \DateTime();
        $this->form = $formRead;
        $this->ip = $ip;
        $this->expires = $expires;
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
}
