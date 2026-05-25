<?php

declare(strict_types=1);

namespace App\DTO;

use App\DTO\ListFiltersDTO;
use Symfony\Component\Validator\Constraints as Assert;

class AddressFiltersDTO extends ListFiltersDTO 
{
    public function __construct(
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Length(max: 255)]
        public readonly ?string $street = null,
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Length(max: 100)]
        public readonly ?string $city = null,
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Length(max: 100)]
        public readonly ?string $region = null,
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Regex(
            pattern: '/^[A-Z0-9 \-]{3,20}$/i',
            message: 'Invalid postal code format'
        )]
        public readonly ?string $postalCode = null,
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Length(max: 100)]
        public readonly ?string $country = null,
        #[Assert\Valid]
        public readonly ?LocationRadiusFilterDTO $location = null,
        #[Assert\Valid]
        public readonly ?LocationAreaFilterDTO $area = null,
    )
    {

    }
}