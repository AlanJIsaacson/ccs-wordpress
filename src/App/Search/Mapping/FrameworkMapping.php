<?php

namespace App\Search\Mapping;

class FrameworkMapping extends AbstractMapping
{

    /**
     * The mapping properties
     *
     * @var array
     */
    protected $properties = [
      'id'               => ['type' => 'integer'],
      'salesforce_id'    => ['type' => 'keyword'],
      'rm_number'        => ['type' => 'keyword'],
      'type'             => ['type' => 'keyword'],
      'description'      => ['type' => 'text'],
      'summary'          => ['type' => 'text'],
      'title'            => [
        'type'   => 'text',
        'fields' => [
          'raw' => ['type' => 'keyword']
        ]
      ],
      'start_date'       => ['type' => 'date'],
      'end_date'         => ['type' => 'date'],
      'terms'            => ['type' => 'keyword'],
      'pillar'           => ['type' => 'keyword'],
      'category'         => ['type' => 'keyword'],
      'status'           => ['type' => 'keyword'],
      'published_status' => ['type' => 'keyword'],
      'lots'             => [
        'type'       => 'nested',
        'properties' => [
          'title'       => ['type' => 'keyword'],
          'description' => ['type' => 'text'],
        ]
      ]
    ];

}