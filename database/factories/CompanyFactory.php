<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Company;

class CompanyFactory extends Factory
{
    /**
     * Khai báo Model tương ứng với Factory này
     */
    protected $model = Company::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company(),
            'email' => $this->faker->unique()->companyEmail(),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'standard_working_days' => 26, // Mặc định 26 ngày công
        ];
    }
}