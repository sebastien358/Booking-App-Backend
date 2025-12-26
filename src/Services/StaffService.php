<?php

namespace App\Services;

class StaffService
{
    public function staffDisplay($staffs, $request, $serializer)
    {
        $elems = ['groups' => ['staff', 'picture'],
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ];

        if (!is_array($staffs)) {
            $dataStaff = $serializer->normalize($staffs, 'json', $elems);

            $urlImage = $request->getSchemeAndHttpHost() . '/images/';
            if (!empty($dataStaff['picture']['filename'])) {
                $dataStaff['picture']['filename'] = $urlImage . $dataStaff['picture']['filename'];
            }

            return $dataStaff;
        }

        $dataStaffs = $serializer->normalize($staffs, 'json', $elems);

        $urlImage = $request->getSchemeAndHttpHost() . '/images/';
        foreach ($dataStaffs as &$dataStaff) {
            if (!empty($dataStaff['picture']['filename'])) {
                $dataStaff['picture']['filename'] = $urlImage . $dataStaff['picture']['filename'];
            }
        }

        return $dataStaffs;
    }
}