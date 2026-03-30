<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\City;
use App\Models\Country;
use App\Models\Governorates;
use App\Models\Regions;
use Illuminate\Database\Seeder;

class BangladeshSeeder extends Seeder
{
    /**
     * Seed complete Bangladesh data: Country, Regions (Divisions), Governorates, Cities (Districts), Areas (Upazilas).
     */
    public function run(): void
    {
        $country = Country::firstOrCreate(
            ['name' => 'Bangladesh'],
            ['name' => 'Bangladesh']
        );

        // Division => Cities => Areas (Upazilas)
        $data = [
            'Dhaka' => [
                'Dhaka' => ['Dhanmondi', 'Gulshan', 'Uttara', 'Mirpur', 'Motijheel', 'Lalbagh'],
                'Gazipur' => ['Gazipur Sadar', 'Kaliakair', 'Kapasia', 'Sreepur'],
                'Narayanganj' => ['Narayanganj Sadar', 'Fatullah', 'Bandar', 'Araihazar'],
                'Tangail' => ['Tangail Sadar', 'Gopalpur', 'Kalihati', 'Mirzapur'],
                'Manikganj' => ['Manikganj Sadar', 'Singair', 'Saturia', 'Ghior'],
                'Munshiganj' => ['Munshiganj Sadar', 'Lohajang', 'Sreenagar', 'Tongibari'],
                'Narsingdi' => ['Narsingdi Sadar', 'Palash', 'Belabo', 'Raipura'],
                'Kishoreganj' => ['Kishoreganj Sadar', 'Bajitpur', 'Kuliarchar', 'Pakundia'],
            ],
            'Chittagong' => [
                'Chittagong' => ['Double Mooring', 'Kotwali', 'Pahartali', 'Patenga', 'Halishahar'],
                'Cox\'s Bazar' => ['Cox\'s Bazar Sadar', 'Teknaf', 'Ukhia', 'Ramu'],
                'Comilla' => ['Comilla Sadar', 'Chandina', 'Laksam', 'Homna'],
                'Chandpur' => ['Chandpur Sadar', 'Hajiganj', 'Matlab North', 'Shahrasti'],
                'Feni' => ['Feni Sadar', 'Chhagalnaiya', 'Daganbhuiyan', 'Parshuram'],
                'Noakhali' => ['Noakhali Sadar', 'Begumganj', 'Chatkhil', 'Companiganj'],
                'Bandarban' => ['Bandarban Sadar', 'Thanchi', 'Ruma', 'Lama'],
                'Rangamati' => ['Rangamati Sadar', 'Kaptai', 'Kawkhali', 'Bilaichhari'],
            ],
            'Rajshahi' => [
                'Rajshahi' => ['Rajshahi Sadar', 'Boalia', 'Motihar', 'Rajpara'],
                'Bogra' => ['Bogra Sadar', 'Sherpur', 'Shibganj', 'Dhunat'],
                'Pabna' => ['Pabna Sadar', 'Ishwardi', 'Chatmohar', 'Bera'],
                'Naogaon' => ['Naogaon Sadar', 'Manda', 'Atrai', 'Raninagar'],
                'Natore' => ['Natore Sadar', 'Singra', 'Gurudaspur', 'Baraigram'],
                'Chapainawabganj' => ['Chapainawabganj Sadar', 'Shibganj', 'Gomastapur', 'Bholahat'],
            ],
            'Khulna' => [
                'Khulna' => ['Khulna Sadar', 'Sonadanga', 'Khalishpur', 'Daulatpur'],
                'Jessore' => ['Jessore Sadar', 'Chaugachha', 'Jhikargachha', 'Monirampur'],
                'Satkhira' => ['Satkhira Sadar', 'Kalaroa', 'Tala', 'Assasuni'],
                'Bagerhat' => ['Bagerhat Sadar', 'Mongla', 'Morrelganj', 'Rampal'],
                'Jhenaidah' => ['Jhenaidah Sadar', 'Kaliganj', 'Kotchandpur', 'Moheshpur'],
                'Magura' => ['Magura Sadar', 'Mohammadpur', 'Shalikha', 'Sreepur'],
                'Narail' => ['Narail Sadar', 'Kalia', 'Lohagara'],
            ],
            'Barisal' => [
                'Barisal' => ['Barisal Sadar', 'Kotwali', 'Band Road', 'Rupatali'],
                'Patuakhali' => ['Patuakhali Sadar', 'Bauphal', 'Dashmina', 'Kalapara'],
                'Pirojpur' => ['Pirojpur Sadar', 'Nazirpur', 'Kawkhali', 'Bhandaria'],
                'Bhola' => ['Bhola Sadar', 'Char Fasson', 'Lalmohan', 'Borhanuddin'],
                'Jhalokati' => ['Jhalokati Sadar', 'Kathalia', 'Nalchity', 'Rajapur'],
                'Barguna' => ['Barguna Sadar', 'Amtali', 'Patharghata', 'Bamna'],
            ],
            'Sylhet' => [
                'Sylhet' => ['Sylhet Sadar', 'Zindabazar', 'Uposhohor', 'Amberkhana'],
                'Moulvibazar' => ['Moulvibazar Sadar', 'Sreemangal', 'Kulaura', 'Rajnagar'],
                'Habiganj' => ['Habiganj Sadar', 'Lakhai', 'Nabiganj', 'Madhabpur'],
                'Sunamganj' => ['Sunamganj Sadar', 'Dharmapasha', 'Chhatak', 'Jagannathpur'],
            ],
            'Rangpur' => [
                'Rangpur' => ['Rangpur Sadar', 'Mithapukur', 'Pirgachha', 'Pirganj'],
                'Dinajpur' => ['Dinajpur Sadar', 'Birampur', 'Birganj', 'Chirirbandar'],
                'Nilphamari' => ['Nilphamari Sadar', 'Saidpur', 'Domar', 'Jaldhaka'],
                'Lalmonirhat' => ['Lalmonirhat Sadar', 'Sadar', 'Patgram', 'Hatibandha'],
                'Kurigram' => ['Kurigram Sadar', 'Nageswari', 'Rajarhat', 'Ulipur'],
                'Thakurgaon' => ['Thakurgaon Sadar', 'Pirganj', 'Baliadangi', 'Ranishankail'],
                'Panchagarh' => ['Panchagarh Sadar', 'Boda', 'Debiganj', 'Atwari'],
            ],
            'Mymensingh' => [
                'Mymensingh' => ['Mymensingh Sadar', 'Muktagachha', 'Trishal', 'Muktagacha'],
                'Jamalpur' => ['Jamalpur Sadar', 'Melandaha', 'Islampur', 'Dewanganj'],
                'Netrokona' => ['Netrokona Sadar', 'Kendua', 'Kalmakanda', 'Durgapur'],
                'Sherpur' => ['Sherpur Sadar', 'Nalitabari', 'Sreebardi', 'Jhenaigati'],
            ],
        ];

        foreach ($data as $divisionName => $citiesWithAreas) {
            $region = Regions::firstOrCreate(
                ['name' => $divisionName, 'country_id' => $country->id],
                ['name_ar' => $divisionName, 'country_id' => $country->id]
            );

            $governorate = Governorates::firstOrCreate(
                ['region_id' => $region->id, 'name' => $divisionName],
                ['name_ar' => $divisionName]
            );

            foreach ($citiesWithAreas as $cityName => $areaNames) {
                $city = City::firstOrCreate(
                    ['name' => $cityName, 'governorate_id' => $governorate->id],
                    ['name_ar' => $cityName, 'governorate_id' => $governorate->id, 'region_id' => $region->id]
                );

                foreach ($areaNames as $areaName) {
                    Area::firstOrCreate(
                        ['name' => $areaName, 'city_id' => $city->id],
                        ['name_ar' => $areaName, 'city_id' => $city->id]
                    );
                }
            }
        }
    }
}
