<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Division;
use App\Models\Document;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Membuat beberapa division
        Division::factory(5)->create(); // Menghasilkan 5 divisi

        // Membuat beberapa user
        User::factory(10)->create(); // Menghasilkan 10 user

        // Membuat beberapa document dengan hubungan ke user dan division
        Document::factory(20)->create(); // Menghasilkan 20 dokumen
    }
}
