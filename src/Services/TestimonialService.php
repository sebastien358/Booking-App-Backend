<?php

namespace App\Services;

class TestimonialService
{
    public function testimonialDisplay($testimonials, $request, $serializer)
    {
        $baseUrl = $request->getSchemeAndHttpHost() . '/images/';

        // 🔹 CAS 1 : DETAIL
        if (!$testimonials instanceof \Traversable && !is_array($testimonials)) {
            $data = $serializer->normalize($testimonials, 'json', [
                'groups' => ['testimonials', 'picture'],
                'circular_reference_handler' => fn ($object) => $object->getId(),
            ]);

            if (!empty($data['picture']['filename'])) {
                $data['picture']['filename'] = $baseUrl . $data['picture']['filename'];
            }

            return $data;
        }

        // 🔹 CAS 2 : LISTE
        $dataList = $serializer->normalize($testimonials, 'json', [
            'groups' => ['testimonials', 'picture'],
            'circular_reference_handler' => fn ($object) => $object->getId(),
        ]);

        foreach ($dataList as &$item) {
            if (!empty($item['picture']['filename'])) {
                $item['picture']['filename'] = $baseUrl . $item['picture']['filename'];
            }
        }

        return $dataList;
    }
}