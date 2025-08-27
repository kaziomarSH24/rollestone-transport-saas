<?php
// app/Helpers/helpers.php
if (!function_exists('tenant')) {
    /**
     * Get the current tenant instance, or a property from the tenant.
     */
    function tenant(?string $key = null)
    {
        if (app()->has('current_tenant')) {
            $company = app('current_tenant'); // This should be set by the IdentifyCompany middleware
            if ($key) {
                return data_get($company, $key);
            }
            return $company;
        }
        return null;
    }
}

if (! function_exists('getDistance')) {
    function getDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Radius of the earth in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance; // distance in kilometers
    }
}

