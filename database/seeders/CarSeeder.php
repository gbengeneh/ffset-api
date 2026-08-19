<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\CarImage;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CarSeeder extends Seeder
{
    /**
     * Demo listings for FFSET Autos so the new site has real, editable
     * inventory to build and screenshot against instead of an empty catalog.
     */
    public function run(): void
    {
        $cars = [
            [
                'make' => 'Toyota', 'model' => 'Land Cruiser Prado', 'year' => 2022,
                'price' => 55000000, 'deposit_amount' => 2000000, 'mileage' => 18000,
                'condition' => 'used', 'transmission' => 'automatic', 'fuel_type' => 'petrol',
                'color' => 'Pearl White',
                'description' => 'A meticulously maintained Prado with full service history, ready for both city runs and long-distance comfort.',
                'features' => ['Leather Seats', 'Sunroof', 'Reverse Camera', 'Cruise Control', 'Third Row Seating'],
                'images' => [
                    'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'make' => 'Mercedes-Benz', 'model' => 'G-Class G63 AMG', 'year' => 2023,
                'price' => 185000000, 'deposit_amount' => 5000000, 'mileage' => 6000,
                'condition' => 'certified_pre_owned', 'transmission' => 'automatic', 'fuel_type' => 'petrol',
                'color' => 'Obsidian Black',
                'description' => 'Bold, powerful, and immaculately kept — the G63 AMG for buyers who want presence and performance in one package.',
                'features' => ['AMG Performance Package', 'Burmester Sound', 'Night Package', 'Heated & Ventilated Seats'],
                'images' => [
                    'https://images.unsplash.com/photo-1520031441872-265e4ff70366?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1617469767053-d3b523a0b982?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'make' => 'Lexus', 'model' => 'RX 350', 'year' => 2021,
                'price' => 42000000, 'deposit_amount' => 1500000, 'mileage' => 32000,
                'condition' => 'used', 'transmission' => 'automatic', 'fuel_type' => 'petrol',
                'color' => 'Graphite Grey',
                'description' => 'A smooth, dependable daily driver with the reliability Lexus is known for and a fully documented ownership history.',
                'features' => ['Leather Seats', 'Reverse Camera', 'Keyless Entry', 'Apple CarPlay'],
                'images' => [
                    'https://images.unsplash.com/photo-1580273916550-e323be2ae537?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'make' => 'Toyota', 'model' => 'Camry', 'year' => 2024,
                'price' => 32000000, 'deposit_amount' => 1000000, 'mileage' => 0,
                'condition' => 'new', 'transmission' => 'automatic', 'fuel_type' => 'petrol',
                'color' => 'Celestial Silver',
                'description' => 'Brand new, zero mileage, straight from the showroom floor — the dependable executive sedan choice.',
                'features' => ['Reverse Camera', 'Cruise Control', 'Bluetooth Audio', 'Alloy Wheels'],
                'images' => [
                    'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'make' => 'Range Rover', 'model' => 'Sport HSE', 'year' => 2022,
                'price' => 95000000, 'deposit_amount' => 3000000, 'mileage' => 21000,
                'condition' => 'used', 'transmission' => 'automatic', 'fuel_type' => 'diesel',
                'color' => 'Santorini Black',
                'description' => 'Commanding road presence with a plush, tech-forward cabin — well maintained and ready for its next owner.',
                'features' => ['Panoramic Roof', 'Meridian Sound', 'Adaptive Cruise Control', 'Air Suspension'],
                'images' => [
                    'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1592840331451-13176a578154?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'make' => 'Honda', 'model' => 'Accord', 'year' => 2020,
                'price' => 18000000, 'deposit_amount' => 700000, 'mileage' => 48000,
                'condition' => 'used', 'transmission' => 'automatic', 'fuel_type' => 'petrol',
                'color' => 'Modern Steel',
                'description' => 'A well-priced, fuel-efficient sedan with a clean interior and dependable service record — great value for first-time buyers.',
                'features' => ['Reverse Camera', 'Bluetooth Audio', 'Alloy Wheels'],
                'images' => [
                    'https://images.unsplash.com/photo-1616422285623-13ff0162193c?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
        ];

        foreach ($cars as $data) {
            $images = $data['images'];
            unset($data['images']);

            $depositProduct = Product::firstOrCreate([
                'name' => "Deposit — {$data['year']} {$data['make']} {$data['model']}",
                'type' => 'service',
                'price' => $data['deposit_amount'],
                'is_stocked' => false,
                'status' => 'active',
            ]);

            $car = Car::firstOrCreate([
                'make' => $data['make'],
                'model' => $data['model'],
                'year' => $data['year'],
            ], [
                ...$data,
                'status' => 'available',
                'deposit_product_id' => $depositProduct->id,
            ]);

            foreach ($images as $index => $imageUrl) {
                CarImage::firstOrCreate([
                    'car_id' => $car->id,
                    'image_url' => $imageUrl,
                ], [
                    'sort_order' => $index,
                ]);
            }
        }
    }
}
