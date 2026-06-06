<?php

namespace Database\Seeders;

use App\Facades\Hashids;
use App\Models\Company;
use App\Models\Setting;
use App\Models\User;
use App\Space\InstallUtils;
use Illuminate\Database\Seeder;
use Silber\Bouncer\BouncerFacade;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'email' => 'ssgls2026@gmail.com',
            'name' => 'SSGLS Admin',
            'role' => 'super admin',
            'password' => 'iloveyouprachi',
        ]);

        $company = Company::create([
            'name' => 'xyz',
            'owner_id' => $user->id,
            'slug' => 'xyz',
        ]);

        $company->unique_hash = Hashids::connection(Company::class)->encode($company->id);
        $company->save();
        $company->setupDefaultData();
        $user->companies()->attach($company->id);
        BouncerFacade::scope()->to($company->id);

        $user->assign('super admin');

        Setting::setSetting('profile_complete', 0);
        // Set version.
        InstallUtils::setCurrentVersion();
    }
}
