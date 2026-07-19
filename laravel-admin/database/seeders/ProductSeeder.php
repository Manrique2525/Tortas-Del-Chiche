<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Promo Tortas', 'price' => 45, 'image' => 'img/imagenes/9.jpg', 'description' => 'La promo más destacada de 3 tortas de cochinita pibil o lechón horneado (incluye cebolla y salsa aparte).', 'category' => 'comida', 'active' => true, 'sort_order' => 1, 'has_mojado' => true, 'has_seco' => true, 'has_cochinita' => true, 'has_lechon' => true],
            ['name' => 'Promo Tacos', 'price' => 40, 'image' => 'img/imagenes/4.jpg', 'description' => '5 deliciosos taquitos de cochinita pibil o lechón horneado (incluye cebolla curtida y salsa aparte).', 'category' => 'comida', 'active' => true, 'sort_order' => 2, 'has_mojado' => true, 'has_seco' => true, 'has_cochinita' => true, 'has_lechon' => true],
            ['name' => 'Taquito de Cochinita', 'price' => 10, 'image' => 'img/imagenes/13.jpg', 'description' => 'Deliciosos tacos de cochinita pibil con tortilla No. 12 (incluye cebolla y salsa aparte).', 'category' => 'comida', 'active' => true, 'sort_order' => 3, 'has_cochinita' => true, 'has_lechon' => true],
            ['name' => 'Torta', 'price' => 16, 'image' => 'img/torta12.jpeg', 'description' => 'Delicioso torta de cochinita pibil o lechón horneado (incluye cebolla curtida y salsa verde aparte).', 'category' => 'comida', 'active' => true, 'sort_order' => 4, 'has_mojado' => true, 'has_seco' => true, 'has_cochinita' => true, 'has_lechon' => true],
            ['name' => 'Kilo', 'price' => 300, 'image' => 'img/imagenes/20.jpg', 'description' => 'Disfruta en familia de nuestra deliciosa cochinita pibil (incluye 3 piezas de sisote, 500g de tortilla, cebolla curtida y salsa verde).', 'category' => 'comida', 'active' => true, 'sort_order' => 5],
            ['name' => 'Medio Kilo', 'price' => 160, 'image' => 'img/imagenes/22.jpg', 'description' => 'Disfruta en familia de nuestra deliciosa cochinita pibil (incluye 2 pz de sisote, 250g de tortilla, cebolla curtida y salsa verde).', 'category' => 'comida', 'active' => true, 'sort_order' => 6],
            ['name' => 'Agua de Horchata', 'price' => 20, 'image' => 'img/horchata_nueva.jpeg', 'description' => 'Bebida tradicional mexicana elaborada con arroz, canela y azúcar.', 'category' => 'bebida', 'active' => true, 'sort_order' => 7],
            ['name' => 'Agua de Jamaica', 'price' => 20, 'image' => 'img/jamaica-nueva.jpeg', 'description' => 'Refrescante infusión de flor de jamaica con un toque ácido y dulce.', 'category' => 'bebida', 'active' => true, 'sort_order' => 8],
            ['name' => 'Agua de Maracuyá', 'price' => 20, 'image' => 'img/maracuya_nueva.jpeg', 'description' => 'Bebida tropical con el sabor exótico y refrescante del maracuyá.', 'category' => 'bebida', 'active' => true, 'sort_order' => 9],
            ['name' => 'Coca-Cola 600 ml', 'price' => 25, 'image' => 'img/coca_nueva.jpeg', 'description' => 'Refresco clásico de cola en botella de 600 ml.', 'category' => 'bebida', 'active' => true, 'sort_order' => 10],
            ['name' => 'Senzao 600 ml', 'price' => 25, 'image' => 'img/sensao_nueva.jpeg', 'description' => 'Refresco de frutas naturales en presentación de 600 ml.', 'category' => 'bebida', 'active' => true, 'sort_order' => 11],
            ['name' => 'Manzanita Sidral 600 ml', 'price' => 25, 'image' => 'img/manzanita_nueva.jpeg', 'description' => 'Refresco sabor manzana en presentación de 600 ml.', 'category' => 'bebida', 'active' => true, 'sort_order' => 12],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
