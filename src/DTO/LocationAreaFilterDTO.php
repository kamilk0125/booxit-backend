<?php

declare(strict_types=1);

namespace App\DTO;

use App\DTO\ListFiltersDTO;
use Symfony\Component\Validator\Constraints as Assert;

class LocationAreaFilterDTO extends ListFiltersDTO 
{
    public function __construct(
        #[Assert\Range(min: -90, max: 90)]
        public readonly float $swLat,
        #[Assert\Range(min: -180, max: 180)]
        public readonly float $swLng,
        #[Assert\Range(min: -90, max: 90)]
        public readonly float $neLat,
        #[Assert\Range(min: -180, max: 180)]
        public readonly float $neLng,
        #[Assert\Range(min: 1, max: 100)]
        public readonly int $radius,
    )
    {

    }
}