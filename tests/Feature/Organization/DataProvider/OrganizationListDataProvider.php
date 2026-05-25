<?php

declare(strict_types=1);

namespace App\Tests\Feature\Organization\DataProvider;

use App\Enum\Service\ServiceCategory;
use App\Tests\Utils\DataProvider\ListDataProvider;

class OrganizationListDataProvider extends ListDataProvider 
{
    public static function filtersDataCases()
    {
        return array_merge([
            [
                [
                    'name' => 'A'
                ],
                [
                    'name' => 'Sorted A'
                ],
            ],
            [
                [   
                    'address' => [
                        'street' => 'Sorted A'
                    ]
                ],
                [
                    'address' => [
                        'street' => 'Sorted A'
                    ]
                ],
            ],
            [
                [   
                    'address' => [
                        'city' => 'Sorted A'
                    ]
                ],
                [
                    'address' => [
                        'city' => 'Sorted A'
                    ]
                ],
            ],
                        [
                [   
                    'address' => [
                        'region' => 'Sorted A'
                    ]
                ],
                [
                    'address' => [
                        'region' => 'Sorted A'
                    ]
                ],
            ],
                        [
                [   
                    'address' => [
                        'postal_code' => '30-126'
                    ]
                ],
                [
                    'address' => [
                        'postal_code' => '30-126'
                    ]
                ],
            ],
                        [
                [   
                    'address' => [
                        'country' => 'Sorted A'
                    ]
                ],
                [
                    'address' => [
                        'country' => 'Sorted A'
                    ]
                ],
            ],
            [
                [   
                    'address' => [
                        'location' => [
                            'lat' => 50.06,
                            'lng' => 19.90,
                            'radius' => 3
                        ]
                    ]
                ],
                [
                    'address' => [
                        'latitude' => 50.06,
                        'longitude' => 19.93
                    ]
                ],
            ],
            [
                [   
                    'address' => [
                        'area' => [
                            'sw_lat' => 50.12,
                            'sw_lng' => 20.10,
                            'ne_lat' => 50.15,
                            'ne_lng' => 20.15,
                            'radius' => 5
                        ]
                    ]
                ],
                [
                    'address' => [
                        'latitude' => 50.08,
                        'longitude' => 20.05
                    ]
                ],
            ],
            [
                [
                    'service_category' => [ServiceCategory::BUSINESS->value]
                ],
                [
                    'name' => 'Sorted B'
                ],
            ],
        ], parent::getTimestampsFiltersDataCases());
    }

    public static function sortingDataCases()
    {
        return array_merge([
            [
                'name',
                parent::getSortedColumnValueSequence('name', 'string')
            ],
            [
                '-name',
                parent::getSortedColumnValueSequence('name', 'string', 'desc')
            ],
        ], parent::getTimestampsSortingDataCases());
    }

    public static function validationDataCases()
    {
        return array_merge(
            parent::validationDataCases(), 
            parent::getTimestampsFiltersValidationDataCases(), 
            [
                [
                    [
                        'filters' => [
                            'name' => '',
                            'address' => [
                                'street' => '',
                                'city' => '',
                                'region' => '',
                                'postal_code' => '',
                                'country' => '',
                                'location' => [
                                    'lat' => -100,
                                    'lng' => -200,
                                    'radius' => 0
                                ],
                                'area' => [
                                    'sw_lat' => -100,
                                    'sw_lng' => -200,
                                    'ne_lat' => -100,
                                    'ne_lng' => -200,
                                    'radius' => 0
                                ],
                            ],
                            'service_category' => ['a'],
                        ]
                    ],
                    [
                        'filters' => [
                            'name' => [
                                'Parameter must be at least 1 characters long'
                            ],
                            'address' => [
                                'street' => [
                                    'This value should not be blank.',
                                ],
                                'city' => [
                                    'This value should not be blank.',
                                ],
                                'region' => [
                                    'This value should not be blank.',
                                ],
                                'postal_code' => [
                                    'This value should not be blank.',
                                ],
                                'country' => [
                                    'This value should not be blank.',
                                ],
                                'location' => [
                                    'lat' => [
                                        'This value should be between -90 and 90.',
                                    ],
                                    'lng' => [
                                        'This value should be between -180 and 180.',
                                    ],
                                    'radius' => [
                                        'This value should be between 1 and 100.',
                                    ],
                                ],
                                'area' => [
                                    'sw_lat' => [
                                        'This value should be between -90 and 90.',
                                    ],
                                    'sw_lng' => [
                                        'This value should be between -180 and 180.',
                                    ],
                                    'ne_lat' => [
                                        'This value should be between -90 and 90.',
                                    ],
                                    'ne_lng' => [
                                        'This value should be between -180 and 180.',
                                    ],
                                    'radius' => [
                                        'This value should be between 1 and 100.',
                                    ],
                                ]
                            ],
                            'service_category' => [
                                'One or more of the given values is invalid, allowed values: '.implode(', ', array_map(fn($val) => '"'.$val.'"', ServiceCategory::values())).'.'
                            ],
                        ]
                    ]
                ],
                [
                    [
                        'filters' => [
                            'name' => str_repeat('a', 55),
                            'address' => [
                                'street' => str_repeat('a',256),
                                'city' => str_repeat('a',101),
                                'region' => str_repeat('a',101),
                                'postal_code' => 'a',
                                'country' => str_repeat('a',101),
                                'location' => [
                                    'lat' => 100,
                                    'lng' => 200,
                                    'radius' => 101
                                ],
                                'area' => [
                                    'sw_lat' => 100,
                                    'sw_lng' => 200,
                                    'ne_lat' => 100,
                                    'ne_lng' => 200,
                                    'radius' => 101
                                ]
                            ],
                        ]
                    ],
                    [
                        'filters' => [
                            'name' => [
                                'Parameter cannot be longer than 50 characters'
                            ],
                            'address' => [
                                'street' => [
                                    'This value is too long. It should have 255 characters or less.',
                                ],
                                'city' => [
                                    'This value is too long. It should have 100 characters or less.',
                                ],
                                'region' => [
                                    'This value is too long. It should have 100 characters or less.',
                                ],
                                'postal_code' => [
                                    'Invalid postal code format',
                                ],
                                'country' => [
                                    'This value is too long. It should have 100 characters or less.',
                                ],
                                'location' => [
                                    'lat' => [
                                        'This value should be between -90 and 90.',
                                    ],
                                    'lng' => [
                                        'This value should be between -180 and 180.',
                                    ],
                                    'radius' => [
                                        'This value should be between 1 and 100.',
                                    ],
                                ],
                                'area' => [
                                    'sw_lat' => [
                                        'This value should be between -90 and 90.',
                                    ],
                                    'sw_lng' => [
                                        'This value should be between -180 and 180.',
                                    ],
                                    'ne_lat' => [
                                        'This value should be between -90 and 90.',
                                    ],
                                    'ne_lng' => [
                                        'This value should be between -180 and 180.',
                                    ],
                                    'radius' => [
                                        'This value should be between 1 and 100.',
                                    ],
                                ]
                            ],
                        ]
                    ]
                ],
            ]
        );
    }
}