<?php

namespace App\Presentation\Serializers;

use League\Fractal\Serializer\DataArraySerializer;

/**
 * Custom serializer that removes the 'data' wrapper from included resources
 */
class NoDataArraySerializer extends DataArraySerializer
{
    /**
     * Serialize a collection.
     *
     * @param string|null $resourceKey
     * @param array  $data
     *
     * @return array
     */
    public function collection(?string $resourceKey, array $data): array
    {
        return ['data' => $data];
    }

    /**
     * Serialize an item.
     *
     * @param string|null $resourceKey
     * @param array  $data
     *
     * @return array
     */
    public function item(?string $resourceKey, array $data): array
    {
        return ['data' => $data];
    }

    /**
     * Serialize null resource.
     *
     * @return array
     */
    public function null(): array
    {
        return ['data' => []];
    }

    /**
     * Serialize the included data.
     *
     * @param string|null $resourceKey
     * @param array  $data
     *
     * @return array
     */
    public function includedData(?string $resourceKey, array $data): array
    {
        // Return the data directly without the 'data' wrapper
        return $data;
    }
}
