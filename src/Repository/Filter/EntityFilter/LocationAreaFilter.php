<?php

declare(strict_types=1);

namespace App\Repository\Filter\EntityFilter;

use App\DTO\LocationAreaFilterDTO;
use Doctrine\ORM\QueryBuilder;

class LocationAreaFilter extends AbstractFieldFilter
{
    public function supports(mixed $value): bool
    {
        return $value instanceof LocationAreaFilterDTO;
    }

    public function apply(QueryBuilder $qb, mixed $value, string $filterId): void
    {
        if(!$value instanceof LocationAreaFilterDTO){
            return;
        }
        
        $radiusM = $value->radius*1000;
        $centerLat = ($value->swLat + $value->neLat) / 2;
        $latDelta = $radiusM / 111000;
        $lngDelta = $radiusM / (111000 * cos(deg2rad($centerLat)));
        $swLat = max(-90.0, $value->swLat - $latDelta);
        $rawSwLng = $value->swLng - $lngDelta;
        $swLng = max(-180.0, $value->swLng - $lngDelta);
        $neLat = min(90.0, $value->neLat + $latDelta);
        $rawNeLng = $value->neLng + $lngDelta;
        $neLng = min(180.0, $rawNeLng);

        $boxes = [
            [
                'swLat' => $swLat,
                'swLng' => $swLng,
                'neLat' => $neLat,
                'neLng' => $neLng
            ]
        ];

        if($rawNeLng > 180.0){
            $boxes[] = [
                'swLat' => $swLat,
                'swLng' => -180.0,
                'neLat' => $neLat,
                'neLng' => $rawNeLng - 360.0
            ];
        }
        else if($rawSwLng < -180.0) {
            $boxes[] = [
                'swLat' => $swLat,
                'swLng' => $rawSwLng + 360.0,
                'neLat' => $neLat,
                'neLng' => 180.0
            ];
        }
        
        $conditions = [];
        foreach($boxes as $i => $box){
            $conditions[] = "CUBE_CONTAINS(EARTH_POINT(:swLat$i, :swLng$i), EARTH_POINT(:neLat$i, :neLng$i), EARTH_POINT($this->qbIdentifier.latitude, $this->qbIdentifier.longitude)) = TRUE";
            $qb->setParameter("swLat$i", $box['swLat'])
            ->setParameter("swLng$i", $box['swLng'])
            ->setParameter("neLat$i", $box['neLat'])
            ->setParameter("neLng$i", $box['neLng']);
        }
        
        $qb->andWhere(implode(' OR ', $conditions));
    }
}