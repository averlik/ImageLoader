<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document(collection: "images")]
class Image
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    private string $filename;

    #[MongoDB\Field(type: 'string')]
    private string $originalUrl;

    #[MongoDB\Field(type: 'int')]
    private int $width;

    #[MongoDB\Field(type: 'int')]
    private int $height;

    #[MongoDB\Field(type: 'string')]
    private string $text;

    #[MongoDB\Field(type: 'date')]
    private \DateTime $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }


    public function getId(): ?string
    {
        return $this->id;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;
        return $this;
    }

    public function getOriginalUrl(): string
    {
        return $this->originalUrl;
    }

    public function setOriginalUrl(string $originalUrl): self
    {
        $this->originalUrl = $originalUrl;
        return $this;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function setWidth(int $width): self
    {
        $this->width = $width;
        return $this;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function setHeight(int $height): self
    {
        $this->height = $height;
        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }
}
