<?php

use App\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    private $data = [
        [
            'keterangan' => 'Petani'
        ],
        [
            'keterangan' => 'LPK'
        ],
        [
            'keterangan' => 'Pengelola Gudang'
        ],
    ];

    public function run()
    {
        collect($this->data)
            ->map(function (array $data) {
                $role = new Role(
                    [
                        'keterangan' => $data['keterangan'],
                    ]
                );
                $role->save();
            })
        ;
    }
}
