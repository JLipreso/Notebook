<?php

namespace App\Http\Controllers\Api;

use App\Models\Barangay;
use App\Models\CityMunicipality;
use App\Models\Province;
use App\Models\Region;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * PSGC address reference data (2026-09-13-005 Phase 005, D-020: no geolocation).
 *
 * Public and cached hard — the PSA republishes quarterly, so a day of staleness
 * is free. Every response selects only the contract columns.
 */
class AddressController extends ApiController
{
    /** PSGC changes quarterly; a day is a conservative TTL. */
    private const TTL = 86400;

    /**
     * GET /api/address/regions
     */
    public function regions(): JsonResponse
    {
        $regions = Cache::remember('address:regions', self::TTL, fn () => Region::query()
            ->select('code', 'name')
            ->orderBy('name')
            ->get());

        return $this->success($regions);
    }

    /**
     * GET /api/address/regions/{code}/provinces
     */
    public function provinces(string $code): JsonResponse
    {
        $provinces = Cache::remember("address:provinces:{$code}", self::TTL, fn () => Province::query()
            ->select('code', 'region_code', 'name')
            ->where('region_code', $code)
            ->orderBy('name')
            ->get());

        return $this->success($provinces);
    }

    /**
     * GET /api/address/provinces/{code}/cities
     */
    public function citiesByProvince(string $code): JsonResponse
    {
        $cities = Cache::remember("address:cities:province:{$code}", self::TTL, fn () => CityMunicipality::query()
            ->select('code', 'province_code', 'region_code', 'name', 'class')
            ->where('province_code', $code)
            ->orderBy('name')
            ->get());

        return $this->success($cities);
    }

    /**
     * GET /api/address/regions/{code}/cities
     *
     * The independent-city branch: cities that belong to a region but to NO
     * province. That is all 31 NCR cities PLUS the 17 Highly Urbanized Cities
     * (Cebu, Davao, Baguio, Iloilo, Bacolod...), which are independent of any
     * province by law — so this is NOT an NCR special case, and every region
     * must be asked for it.
     */
    public function citiesByRegion(string $code): JsonResponse
    {
        $cities = Cache::remember("address:cities:region:{$code}", self::TTL, fn () => CityMunicipality::query()
            ->select('code', 'province_code', 'region_code', 'name', 'class')
            ->where('region_code', $code)
            ->whereNull('province_code')
            ->orderBy('name')
            ->get());

        return $this->success($cities);
    }

    /**
     * GET /api/address/cities/{code}/barangays
     */
    public function barangays(string $code): JsonResponse
    {
        $barangays = Cache::remember("address:barangays:{$code}", self::TTL, fn () => Barangay::query()
            ->select('code', 'city_muni_code', 'name')
            ->where('city_muni_code', $code)
            ->orderBy('name')
            ->get());

        return $this->success($barangays);
    }

    /**
     * GET /api/address/barangays/{code}/chain
     *
     * Resolves a saved barangay_code back up to its full chain, so a profile
     * screen can re-display the address without the client walking four
     * endpoints on load.
     */
    public function chain(string $code): JsonResponse
    {
        $chain = Cache::remember("address:chain:{$code}", self::TTL, function () use ($code) {
            $barangay = Barangay::select('code', 'city_muni_code', 'name')->find($code);

            if ($barangay === null) {
                return null;
            }

            $city = CityMunicipality::select('code', 'province_code', 'region_code', 'name', 'class')
                ->find($barangay->city_muni_code);

            $province = $city?->province_code === null
                ? null
                : Province::select('code', 'region_code', 'name')->find($city->province_code);

            $region = $city === null
                ? null
                : Region::select('code', 'name')->find($city->region_code);

            return [
                'region' => $region,
                'province' => $province,
                'city_municipality' => $city,
                'barangay' => $barangay,
            ];
        });

        if ($chain === null) {
            return $this->notFound('Barangay not found.');
        }

        return $this->success($chain);
    }
}
