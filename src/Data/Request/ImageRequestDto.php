<?php

namespace App\Data\Request;

use Symfony\Component\Validator\Constraints as Assert;

class ImageRequestDto
{
    public function __construct(

        #[Assert\NotBlank(message: 'URL обязателен')]
        #[Assert\Url(message: 'Некорректный URL')]
        public ?string $url = null,

        #[Assert\PositiveOrZero]
        public ?int    $minWidth = null,

        #[Assert\PositiveOrZero]
        public ?int    $minHeight = null,

        #[Assert\Length(max: 100)]
        public ?string $text = null,
    )
    {
    }
}