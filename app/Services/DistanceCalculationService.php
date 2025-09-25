<?php

namespace App\Services;

class DistanceCalculationService
{
    /**
     * Calculate the distance between two GPS coordinates using the Haversine formula
     * 
     * @param float $lat1 Latitude of first point
     * @param float $lon1 Longitude of first point
     * @param float $lat2 Latitude of second point
     * @param float $lon2 Longitude of second point
     * @param string $unit Unit of measurement (K for kilometers, M for miles, N for nautical miles)
     * @return float Distance between the two points
     */
    public static function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2, string $unit = 'K'): float
    {
        if (($lat1 == $lat2) && ($lon1 == $lon2)) {
            return 0;
        }

        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $dist = $dist * 60 * 1.1515;

        switch ($unit) {
            case 'M':
                return $dist;
            case 'K':
                return $dist * 1.609344;
            case 'N':
                return $dist * 0.8684;
            default:
                return $dist * 1.609344; // Default to kilometers
        }
    }

    /**
     * Calculate the distance between two GPS coordinates and return in kilometers
     * 
     * @param float $lat1 Latitude of first point
     * @param float $lon1 Longitude of first point
     * @param float $lat2 Latitude of second point
     * @param float $lon2 Longitude of second point
     * @return float Distance in kilometers
     */
    public static function calculateDistanceInKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        return self::calculateDistance($lat1, $lon1, $lat2, $lon2, 'K');
    }

    /**
     * Calculate the distance between two GPS coordinates and return in meters
     * 
     * @param float $lat1 Latitude of first point
     * @param float $lon1 Longitude of first point
     * @param float $lat2 Latitude of second point
     * @param float $lon2 Longitude of second point
     * @return float Distance in meters
     */
    public static function calculateDistanceInMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        return self::calculateDistanceInKm($lat1, $lon1, $lat2, $lon2) * 1000;
    }

    /**
     * Calculate time difference between two datetime strings
     * 
     * @param string $startTime Start time (ISO 8601 format)
     * @param string $endTime End time (ISO 8601 format)
     * @return array Array containing hours, minutes, and total minutes
     */
    public static function calculateTimeDifference(string $startTime, string $endTime): array
    {
        $start = new \DateTime($startTime);
        $end = new \DateTime($endTime);
        $diff = $start->diff($end);

        $totalMinutes = ($diff->h * 60) + $diff->i;
        if ($diff->invert) {
            $totalMinutes = -$totalMinutes;
        }

        return [
            'hours' => $diff->h,
            'minutes' => $diff->i,
            'total_minutes' => $totalMinutes,
            'formatted' => sprintf('%02d:%02d', $diff->h, $diff->i)
        ];
    }
}
