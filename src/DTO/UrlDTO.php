<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
class UrlDTO
{
    #[Assert\NotBlank]
    #[Assert\Url]
    public string $extId;

    public function __construct(string $extId = '',)
    {
        $this->extId = $extId;
    }
}
