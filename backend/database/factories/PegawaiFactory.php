<?php

namespace Database\Factories;

use App\Models\Pegawai;
use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PegawaiFactory extends Factory
{
    protected $model = Pegawai::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'nama' => $this->faker->name(),
            'nik' => $this->faker->numerify('################'),
            'nip' => $this->faker->unique()->bothify('PEG-202610-####'),
            'no_telp' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'avatar' => null,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Pegawai $pegawai) {
            $role = Role::where('name', 'Staff')->first();
            
            User::create([
                'name' => $pegawai->nama,
                'email' => $pegawai->email,
                'password' => Hash::make('password'),
                'role_id' => $role ? $role->id : null,
                'status' => 'Aktif',
                'avatar' => $pegawai->avatar,
                'pegawai_id' => $pegawai->id,
            ]);
        });
    }
}
