<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

         //Roles
        $admin = Role::create(['name' => 'admin']);
        $owner = Role::create(['name' => 'owner']);
        $owner = Role::create(['name' => 'client']);

    
    }
}
