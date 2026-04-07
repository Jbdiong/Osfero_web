<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Illuminate\Database\Seeder;

class CountryStateCitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            // Kuala Lumpur Suburbs
            [ 'city' => 'Alam Damai', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Ampang', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Bandar Menjalara', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Bandar Sri Permaisuri', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Bandar Tasik Selatan', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Bandar Tun Razak', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Bangsar', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Bangsar Park', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Bangsar South', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Batu 11 Cheras', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Batu', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Brickfields', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Bukit Bintang', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Bukit Jalil', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Bukit Kiara', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Bukit Nanas', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Bukit Petaling', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Bukit Tunku', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Cheras', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Chow Kit', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Damansara Heights', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Damansara Town Centre', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Dang Wangi', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Desa Petaling', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Federal Hill', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Happy Garden', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Jalan Cochrane', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Jinjang', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Kampung Baru', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Kampung Datuk Keramat', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Kampung Kasipillay', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Kampung Padang Balang', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Kepong', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Kepong Baru', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'KL Eco City', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Kuchai Lama', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Lembah Pantai', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Maluri', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Medan Tuanku', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Miharja', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Mont Kiara', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Pantai Dalam', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Pudu', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Putrajaya', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Salak South', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Segambut', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Semarak', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Sentul', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Setapak', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Setiawangsa', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Shamelin Perkasa', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Sri Hartamas', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Sri Petaling', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Sungai Besi', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Taman Bukit Maluri', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Taman Connaught', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Taman Desa', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Taman Duta', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Taman Ibukota', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Taman Len Seng', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Taman Melati', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Taman Midah', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Taman OUG', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Taman P. Ramlee', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Taman Sri Sinar', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Taman Taynton View', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Taman Tun Dr Ismail', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Taman U-Thant', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Taman Wahyu', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Titiwangsa', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Wangsa Maju', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            [ 'city' => 'Kuala Lumpur', 'state' => 'Kuala Lumpur', 'country' => 'Malaysia' ],
            
            // Selangor
            [ 'city' => 'Petaling Jaya', 'state' => 'Selangor', 'country' => 'Malaysia' ],
            [ 'city' => 'Shah Alam', 'state' => 'Selangor', 'country' => 'Malaysia' ],
            [ 'city' => 'Subang Jaya', 'state' => 'Selangor', 'country' => 'Malaysia' ],
            
            // Penang
            [ 'city' => 'George Town', 'state' => 'Penang', 'country' => 'Malaysia' ],
            [ 'city' => 'Butterworth', 'state' => 'Penang', 'country' => 'Malaysia' ],
            
            // Johor
            [ 'city' => 'Johor Bahru', 'state' => 'Johor', 'country' => 'Malaysia' ],
            [ 'city' => 'Iskandar Puteri', 'state' => 'Johor', 'country' => 'Malaysia' ],
            
            // Melaka
            [ 'city' => 'Melaka City', 'state' => 'Melaka', 'country' => 'Malaysia' ],
            
            // Perak
            [ 'city' => 'Ipoh', 'state' => 'Perak', 'country' => 'Malaysia' ],
            
            // Negeri Sembilan
            [ 'city' => 'Seremban', 'state' => 'Negeri Sembilan', 'country' => 'Malaysia' ],
            
            // Pahang
            [ 'city' => 'Kuantan', 'state' => 'Pahang', 'country' => 'Malaysia' ],
            
            // Terengganu
            [ 'city' => 'Kuala Terengganu', 'state' => 'Terengganu', 'country' => 'Malaysia' ],
            
            // Kelantan
            [ 'city' => 'Kota Bharu', 'state' => 'Kelantan', 'country' => 'Malaysia' ],
            
            // Perlis
            [ 'city' => 'Kangar', 'state' => 'Perlis', 'country' => 'Malaysia' ],
            
            // Kedah
            [ 'city' => 'Alor Setar', 'state' => 'Kedah', 'country' => 'Malaysia' ],
            
            // Sarawak
            [ 'city' => 'Kuching', 'state' => 'Sarawak', 'country' => 'Malaysia' ],
            [ 'city' => 'Miri', 'state' => 'Sarawak', 'country' => 'Malaysia' ],
            
            // Sabah
            [ 'city' => 'Kota Kinabalu', 'state' => 'Sabah', 'country' => 'Malaysia' ],
            [ 'city' => 'Sandakan', 'state' => 'Sabah', 'country' => 'Malaysia' ],
            
            // Putrajaya (as separate state)
            [ 'city' => 'Putrajaya', 'state' => 'Putrajaya', 'country' => 'Malaysia' ],
            
            // Labuan (as separate state)
            [ 'city' => 'Labuan', 'state' => 'Labuan', 'country' => 'Malaysia' ],
            
            // Hong Kong
            [ 'city' => 'Central and Western', 'state' => 'Hong Kong Island', 'country' => 'Hong Kong' ],
            [ 'city' => 'Wan Chai', 'state' => 'Hong Kong Island', 'country' => 'Hong Kong' ],
            [ 'city' => 'Eastern', 'state' => 'Hong Kong Island', 'country' => 'Hong Kong' ],
            [ 'city' => 'Southern', 'state' => 'Hong Kong Island', 'country' => 'Hong Kong' ],
            [ 'city' => 'Yau Tsim Mong', 'state' => 'Kowloon', 'country' => 'Hong Kong' ],
            [ 'city' => 'Sham Shui Po', 'state' => 'Kowloon', 'country' => 'Hong Kong' ],
            [ 'city' => 'Kowloon City', 'state' => 'Kowloon', 'country' => 'Hong Kong' ],
            [ 'city' => 'Wong Tai Sin', 'state' => 'Kowloon', 'country' => 'Hong Kong' ],
            [ 'city' => 'Kwun Tong', 'state' => 'Kowloon', 'country' => 'Hong Kong' ],
            [ 'city' => 'Sha Tin', 'state' => 'New Territories', 'country' => 'Hong Kong' ],
            [ 'city' => 'Tai Po', 'state' => 'New Territories', 'country' => 'Hong Kong' ],
            [ 'city' => 'North', 'state' => 'New Territories', 'country' => 'Hong Kong' ],
            [ 'city' => 'Sai Kung', 'state' => 'New Territories', 'country' => 'Hong Kong' ],
            [ 'city' => 'Tsuen Wan', 'state' => 'New Territories', 'country' => 'Hong Kong' ],
            [ 'city' => 'Tuen Mun', 'state' => 'New Territories', 'country' => 'Hong Kong' ],
            [ 'city' => 'Yuen Long', 'state' => 'New Territories', 'country' => 'Hong Kong' ],
            [ 'city' => 'Islands', 'state' => 'New Territories', 'country' => 'Hong Kong' ],
            
            // Singapore (treat '-' as 'Singapore' state)
            [ 'city' => 'Central Area', 'state' => 'Singapore', 'country' => 'Singapore' ],
            [ 'city' => 'Tampines', 'state' => 'Singapore', 'country' => 'Singapore' ],
            [ 'city' => 'Jurong West', 'state' => 'Singapore', 'country' => 'Singapore' ],
            [ 'city' => 'Woodlands', 'state' => 'Singapore', 'country' => 'Singapore' ],
            [ 'city' => 'Bedok', 'state' => 'Singapore', 'country' => 'Singapore' ],
            [ 'city' => 'Yishun', 'state' => 'Singapore', 'country' => 'Singapore' ],
            [ 'city' => 'Hougang', 'state' => 'Singapore', 'country' => 'Singapore' ],
            [ 'city' => 'Bukit Batok', 'state' => 'Singapore', 'country' => 'Singapore' ],
            [ 'city' => 'Choa Chu Kang', 'state' => 'Singapore', 'country' => 'Singapore' ],
            [ 'city' => 'Pasir Ris', 'state' => 'Singapore', 'country' => 'Singapore' ],
            [ 'city' => 'Serangoon', 'state' => 'Singapore', 'country' => 'Singapore' ],
            [ 'city' => 'Clementi', 'state' => 'Singapore', 'country' => 'Singapore' ],
            
            // China
            [ 'city' => 'Beijing', 'state' => 'Beijing', 'country' => 'China' ],
            [ 'city' => 'Shanghai', 'state' => 'Shanghai', 'country' => 'China' ],
            [ 'city' => 'Guangzhou', 'state' => 'Guangdong', 'country' => 'China' ],
            [ 'city' => 'Shenzhen', 'state' => 'Guangdong', 'country' => 'China' ],
            [ 'city' => 'Chengdu', 'state' => 'Sichuan', 'country' => 'China' ],
            [ 'city' => 'Hangzhou', 'state' => 'Zhejiang', 'country' => 'China' ],
            [ 'city' => 'Wuhan', 'state' => 'Hubei', 'country' => 'China' ],
            [ 'city' => 'Nanjing', 'state' => 'Jiangsu', 'country' => 'China' ],
            [ 'city' => 'Tianjin', 'state' => 'Tianjin', 'country' => 'China' ],
            [ 'city' => 'Xi\'an', 'state' => 'Shaanxi', 'country' => 'China' ],
            [ 'city' => 'Suzhou', 'state' => 'Jiangsu', 'country' => 'China' ],
            [ 'city' => 'Qingdao', 'state' => 'Shandong', 'country' => 'China' ],
            [ 'city' => 'Harbin', 'state' => 'Heilongjiang', 'country' => 'China' ],
            [ 'city' => 'Changsha', 'state' => 'Hunan', 'country' => 'China' ],
            [ 'city' => 'Zhengzhou', 'state' => 'Henan', 'country' => 'China' ],
        ];

        // Group locations by country and state
        $grouped = [];
        foreach ($locations as $location) {
            $country = $location['country'];
            $state = $location['state'] === '-' ? 'Singapore' : $location['state'];
            $city = $location['city'];
            
            if (!isset($grouped[$country])) {
                $grouped[$country] = [];
            }
            if (!isset($grouped[$country][$state])) {
                $grouped[$country][$state] = [];
            }
            $grouped[$country][$state][] = $city;
        }

        // Create countries, states, and cities
        foreach ($grouped as $countryName => $states) {
            $country = Country::firstOrCreate(['name' => $countryName]);

            foreach ($states as $stateName => $cities) {
                $state = State::firstOrCreate([
                    'country_id' => $country->id,
                    'name' => $stateName,
                ]);

                foreach ($cities as $cityName) {
                    City::firstOrCreate([
                        'state_id' => $state->id,
                        'name' => $cityName,
                    ]);
                }
            }
        }

        $this->command->info('Countries, states, and cities seeded successfully!');
        $this->command->info('Total countries: ' . Country::count());
        $this->command->info('Total states: ' . State::count());
        $this->command->info('Total cities: ' . City::count());
    }
}
