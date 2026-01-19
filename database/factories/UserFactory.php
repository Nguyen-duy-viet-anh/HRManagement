<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition()
    {
        // Tạo một ID ngẫu nhiên để ảnh không bị trùng lặp giữa các User
        $randomId = $this->faker->numberBetween(1, 1000);
        $gender = $this->faker->randomElement(['male', 'female']);

        return [
            'name' => $this->faker->name($gender),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => bcrypt('123456'), 
            'role' => 2,
            'status' => 1,
            'company_id' => null, 
            'base_salary' => $this->faker->numberBetween(8000000, 20000000),
            
            // --- THÊM AVATAR NGẪU NHIÊN ---
            // Sử dụng Picsum để lấy ảnh chân dung ngẫu nhiên
            'avatar' => "https://picsum.photos/200/200?random={$randomId}",
            
            // --- MỞ LẠI CÁC CỘT CÁ NHÂN ---
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'birthday' => $this->faker->date('Y-m-d', '-20 years'), // Sinh nhật cách đây tầm 20 năm
            'gender' => $gender,
        ];
    }
}