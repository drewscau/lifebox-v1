<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\TagProperty;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TagsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tags = [
            'BILLS' => ['Supplier', 'What', 'Date', 'Account Number', 'Website', 'Phone'],
            'LEGAL & TAXES' => ['Prepared By', 'What', 'Date', 'Further Info', 'Company Website', 'Phone'],
            'PURCHASES & WARRANTIES' => ['Retailer', 'Purchased', 'Date', 'Warrant Period', 'Product Website'],
            'IDENTIFICATION' => ['Type', 'What', 'Expiry', 'Photo', 'Website', 'Phone'],
            'HEALTH & INSURANCES' => ['Supplier', 'Coverage', 'Expiry', 'Policy Number', 'Website', 'Phone'],
            'BANKING & FINANCE' => ['Bank', 'Name', 'Date Opened', 'Account Number', 'Photo', 'Website', 'Branch'],
            'RECEIPTS' => ['Retailer', 'What', 'Date Purchased', 'Value', 'Photo'],
            'OTHER INVESTMENTS' => ['Provider', 'What', 'Start Date', 'Account/Reference', 'Website', 'Value'],
            'STATEMENTS' => ['Institution', 'Type of Statement', 'Start Date'],
            'OTHER' => [],
        ];

        foreach ($tags as $tag => $props) {
            $t = Tag::firstOrCreate([
                'system_created' => true,
                'tag_name' => $tag
            ], [
                'tag_description' => $tag
            ]);

            foreach ($props as $prop) {
                TagProperty::create([
                    'tag_id' => $t->id,
                    'name' => $prop,
                    'type' => $this->getType(strtolower($prop)),
                    'system_created' => true,
                ]);
            }
        }
    }

    private function getType($name)
    {
        if (str_contains($name, 'phone')) {
            return 'phone';
        }
        if (str_contains($name, 'website')) {
            return 'website';
        }
        if (str_contains($name, 'expiry')) {
            return 'date';
        }
        if (str_contains($name, 'date')) {
            return 'date';
        }
        if (str_contains($name, 'purchased')) {
            return 'date';
        }
        if (str_contains($name, 'date')) {
            return 'date';
        }
        if (str_contains($name, 'mobile')) {
            return 'phone';
        }
        if (str_contains($name, 'link')) {
            return 'website';
        }
        return 'other';
    }
}
