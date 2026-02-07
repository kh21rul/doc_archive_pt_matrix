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

        User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@gmail.com',
            'role' => 'superadmin',
        ]);

        Division::insert([
            ['name' => 'Management (MNG)', 'description' => 'Divisi yang bertanggung jawab atas perencanaan dan pengelolaan operasional perusahaan secara keseluruhan.'],
            ['name' => 'Human Resources (HR)', 'description' => 'Divisi yang mengelola sumber daya manusia, termasuk rekrutmen, pelatihan, dan kesejahteraan karyawan.'],
            ['name' => 'IT dan General Affair (GAF)', 'description' => 'Divisi yang mengelola sistem informasi dan infrastruktur teknologi serta urusan umum perusahaan.'],
            ['name' => 'Marketing (MKT)', 'description' => 'Divisi yang bertanggung jawab untuk memasarkan produk atau layanan perusahaan dan menjaga hubungan dengan pelanggan.'],
            ['name' => 'Permit (LIN)', 'description' => 'Divisi yang mengurus izin dan perizinan untuk operasional perusahaan sesuai dengan peraturan yang berlaku.'],
            ['name' => 'Health Safety Environment (HSE)', 'description' => 'Divisi yang mengawasi keselamatan kerja, kesehatan, dan perlindungan lingkungan di perusahaan.'],
            ['name' => 'Magazine (MGZ)', 'description' => 'Divisi yang mengelola penerbitan dan distribusi majalah internal perusahaan serta komunikasi media.'],
            ['name' => 'Procurement and Logistic (LOG)', 'description' => 'Divisi yang bertanggung jawab atas pengadaan barang dan jasa serta pengelolaan logistik perusahaan.'],
            ['name' => 'Finance (FIN)', 'description' => 'Divisi yang mengelola keuangan perusahaan, termasuk pembukuan, pengelolaan anggaran, dan laporan keuangan.']
        ]);
    }
}
