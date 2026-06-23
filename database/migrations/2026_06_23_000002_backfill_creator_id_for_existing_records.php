<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaultUser = DB::table('users')->where('name', 'SS Gujarat Logistics')->first();
        if (!$defaultUser) {
            $defaultUser = DB::table('users')->insertGetId([
                'name' => 'SS Gujarat Logistics',
                'email' => 'ssgujarat@logistics.com',
                'password' => bcrypt(bin2hex(random_bytes(32))),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $defaultUser = $defaultUser->id;
        }

        DB::table('invoices')->whereNull('creator_id')->update(['creator_id' => $defaultUser]);
        DB::table('expenses')->whereNull('creator_id')->update(['creator_id' => $defaultUser]);
        DB::table('payments')->whereNull('creator_id')->update(['creator_id' => $defaultUser]);
        DB::table('customers')->whereNull('creator_id')->update(['creator_id' => $defaultUser]);
        DB::table('estimates')->whereNull('creator_id')->update(['creator_id' => $defaultUser]);
        DB::table('items')->whereNull('creator_id')->update(['creator_id' => $defaultUser]);
    }

    public function down(): void
    {
        $userId = DB::table('users')->where('name', 'SS Gujarat Logistics')->value('id');
        if ($userId) {
            DB::table('invoices')->where('creator_id', $userId)->update(['creator_id' => null]);
            DB::table('expenses')->where('creator_id', $userId)->update(['creator_id' => null]);
            DB::table('payments')->where('creator_id', $userId)->update(['creator_id' => null]);
            DB::table('customers')->where('creator_id', $userId)->update(['creator_id' => null]);
            DB::table('estimates')->where('creator_id', $userId)->update(['creator_id' => null]);
            DB::table('items')->where('creator_id', $userId)->update(['creator_id' => null]);
            DB::table('users')->where('name', 'SS Gujarat Logistics')->delete();
        }
    }
};
