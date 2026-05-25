<?php

namespace App\Entity\Embeddable;

use App\Enum\NormalizerGroup;
use App\Repository\Filter\EntityFilter\FieldValue;
use App\Repository\Filter\EntityFilter\LocationAreaFilter;
use App\Repository\Filter\EntityFilter\LocationRadiusFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Embeddable]
class Address
{
    #[Groups([NormalizerGroup::ADDRESS->value])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $street = null;

    #[Groups([NormalizerGroup::ADDRESS->value])]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $city = null;

    #[Groups([NormalizerGroup::ADDRESS->value])]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $region = null;

    #[Groups([NormalizerGroup::ADDRESS->value])]
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $postalCode = null;

    #[Groups([NormalizerGroup::ADDRESS->value])]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $country = null;

    #[Groups([NormalizerGroup::ADDRESS->value])]
    #[ORM\Column(length: 255)]
    private ?string $placeId = null;

    #[Groups([NormalizerGroup::ADDRESS->value])]
    #[ORM\Column(type: 'text')]
    private ?string $formattedAddress = null;

    #[Groups([NormalizerGroup::ADDRESS->value])]
    #[ORM\Column(type: 'float')]
    private ?float $latitude = null;

    #[Groups([NormalizerGroup::ADDRESS->value])]
    #[ORM\Column(type: 'float')]
    private ?float $longitude = null;

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): self
    {
        $this->street = $street;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): self
    {
        $this->region = $region;
        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): self
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }
    
    public function setCountry(?string $country): self
    {
        $this->country = $country;
        return $this;
    }

    public function getPlaceId(): ?string
    {
        return $this->placeId;
    }

    public function setPlaceId(string $placeId): self
    {
        $this->placeId = $placeId;
        return $this;
    }

    public function getFormattedAddress(): ?string
    {
        return $this->formattedAddress;
    }

    public function setFormattedAddress(string $formattedAddress): self
    {
        $this->formattedAddress = $formattedAddress;
        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    public static function getFilterDefs(): array
    {
        return array_merge([
            'street' => new FieldValue('street', '='),
            'city' => new FieldValue('city', '='),
            'region' => new FieldValue('region', '='),
            'postalCode' => new FieldValue('postalCode', '='),
            'country' => new FieldValue('country', '='),
            'location' => new LocationRadiusFilter(''),
            'area' => new LocationAreaFilter('')
        ]);
    }
}
