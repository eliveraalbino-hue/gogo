<?php

namespace Database\Seeders;

use App\Models\TrafficIncident;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TrafficIncidentSeeder extends Seeder {
    public function run(): void {
        \App\Models\TrafficIncident::unguard();
        TrafficIncident::truncate();

        $csvFilePath = base_path("database/data/data_mmda_traffic_spatial.csv");
        
        if (!file_exists($csvFilePath)) {
            $this->command->error("CSV file not found at: {$csvFilePath}");
            return;
        }

        $handle = fopen($csvFilePath, "r");
        $firstline = true;
        $count = 0;

        while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
            if (empty($data) || !isset($data[0]) || trim($data[0]) === '') {
                continue;
            }

            if ($firstline) {
                $firstline = false;
                continue;
            }

            // UTF-8 Clean string conversion to drop hidden Windows formatting bytes
            $dateRaw     = isset($data[0]) ? trim(mb_convert_encoding($data[0], "UTF-8", "UTF-8")) : '';
            $timeRaw     = isset($data[1]) ? trim(mb_convert_encoding($data[1], "UTF-8", "UTF-8")) : '';
            $city        = isset($data[2]) ? trim(mb_convert_encoding($data[2], "UTF-8", "UTF-8")) : '';
            $street      = isset($data[3]) ? trim(mb_convert_encoding($data[3], "UTF-8", "UTF-8")) : '';
            $latitude    = isset($data[4]) ? (float)$data[4] : 0;
            $longitude   = isset($data[5]) ? (float)$data[5] : 0;
            $rawType     = isset($data[8]) ? trim(mb_convert_encoding($data[8], "UTF-8", "UTF-8")) : 'OTHER';

            if ($latitude == 0 || $longitude == 0) {
                continue;
            }

            $combinedLocation = trim(($city ? $city . ' - ' : '') . $street);

            $rawTypeUpper = strtoupper($rawType);
            if (str_contains($rawTypeUpper, 'ACCIDENT') || str_contains($rawTypeUpper, 'COLLISION') || str_contains($rawTypeUpper, 'HIT AND RUN')) {
                $incidentType = 'Vehicular Accident';
            } elseif (str_contains($rawTypeUpper, 'STALLED') || str_contains($rawTypeUpper, 'MECHANICAL') || str_contains($rawTypeUpper, 'TIRE')) {
                $incidentType = 'Stalled Vehicle';
            } elseif (str_contains($rawTypeUpper, 'ROAD') || str_contains($rawTypeUpper, 'DPWH') || str_contains($rawTypeUpper, 'PATCHING') || str_contains($rawTypeUpper, 'OBSTRUCTION')) {
                $incidentType = 'Roadwork & Obstruction';
            } else {
                $incidentType = 'Other';
            }

            $occurredAt = null;
            if (!empty($dateRaw) && !empty($timeRaw)) {
                try {
                    $occurredAt = Carbon::parse($dateRaw . ' ' . $timeRaw);
                } catch (\Exception $e) {
                    $occurredAt = null;
                }
            }

            TrafficIncident::create([
                "incident_type" => substr($incidentType, 0, 50),
                "description"   => substr($rawType, 0, 250),
                "location"      => substr($combinedLocation, 0, 200),
                "latitude"      => $latitude,
                "longitude"     => $longitude,
                "occurred_at"   => $occurredAt,
            ]);

            $count++;
        }

        fclose($handle);
        $this->command->info("Successfully seeded {$count} records into the MMDA Database!");
    }
}