<?php

namespace Database\Seeders;

use App\Models\AddOnItem;
use App\Models\SnookerTable;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@147summit.test'],
            [
                'name' => '147 Summit Admin',
                'role' => 'admin',
                'password' => 'password',
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'manager@147summit.test'],
            [
                'name' => 'Sale Manager',
                'role' => 'sale_manager',
                'password' => 'password',
            ],
        );

        foreach (range(1, 4) as $number) {
            SnookerTable::query()->updateOrCreate(
                ['number' => $number],
                [
                    'name' => "Table {$number}",
                    'hourly_rate' => 10,
                    'is_active' => true,
                ],
            );
        }

        Staff::query()->updateOrCreate(
            ['name' => 'Club Manager'],
            [
                'role' => 'manager',
                'commission_rate' => 25,
                'is_active' => true,
            ],
        );

        foreach ([
            ['name' => 'Tea', 'unit_price' => 80],
            ['name' => 'Cold Drink', 'unit_price' => 120],
            ['name' => 'Water', 'unit_price' => 60],
            ['name' => 'Snacks', 'unit_price' => 150],
        ] as $item) {
            AddOnItem::query()->updateOrCreate(
                ['name' => $item['name']],
                [
                    'unit_price' => $item['unit_price'],
                    'is_active' => true,
                ],
            );
        }
    }
}
