<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\FinancialAccount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create(['name' => 'Demo User', 'email' => 'demo@example.com']);
        FinancialAccount::firstOrCreate(['user_id' => $user->id, 'name' => 'Cash'], ['type' => 'cash', 'opening_balance' => 0]);
        foreach ([['Food','expense','food','#B34735'],['Shopping','expense','shopping','#7A52B9'],['Transport','expense','transport','#A8750D'],['Other','expense','more','#637083'],['Salary','income','work','#16815B'],['Other','income','more','#16815B']] as [$name,$type,$icon,$color]) {
            $user->categories()->updateOrCreate(compact('name', 'type'), compact('name', 'type', 'icon', 'color'));
        }
    }
}
