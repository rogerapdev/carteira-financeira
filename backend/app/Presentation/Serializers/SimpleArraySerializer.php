<?php

namespace App\Presentation\Serializers;

use League\Fractal\Serializer\ArraySerializer;

/**
 * A simpler serializer that uses a different approach to handle included data
 */
class SimpleArraySerializer extends ArraySerializer
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
}
