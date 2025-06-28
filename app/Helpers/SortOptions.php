<?php

namespace App\Helpers;

class SortOptions
{
    public static function list(): array
    {
        return [
            [
                'key' => 'price_htl',
                'en' => 'Price: High to Low',
                'ar' => 'السعر: من الأعلى إلى الأدنى',
                'column' => 'products.price',
                'direction' => 'desc',
            ],
            [
                'key' => 'price_lth',
                'en' => 'Price: Low to High',
                'ar' => 'السعر: من الأدنى إلى الأعلى',
                'column' => 'products.price',
                'direction' => 'asc',
            ],
            [
                'key' => 'newest',
                'en' => 'Newest First',
                'ar' => 'الأحدث أولاً',
                'column' => 'products.created_at',
                'direction' => 'desc',
            ],
            [
                'key' => 'oldest',
                'en' => 'Oldest First',
                'ar' => 'الأقدم أولاً',
                'column' => 'products.created_at',
                'direction' => 'asc',
            ],
            [
                'key' => 'name_asc',
                'en' => 'Name: A to Z',
                'ar' => 'الاسم: من أ إلى ي',
                'column' => 'products.name',
                'direction' => 'asc',
            ],
            [
                'key' => 'name_desc',
                'en' => 'Name: Z to A',
                'ar' => 'الاسم: من ي إلى أ',
                'column' => 'products.name',
                'direction' => 'desc',
            ],
            [
                'key' => 'discount_htl',
                'en' => 'Discount: High to Low',
                'ar' => 'الخصم: من الأعلى إلى الأدنى',
                'column' => 'products.discount',
                'direction' => 'desc',
            ],
            [
                'key' => 'discount_lth',
                'en' => 'Discount: Low to High',
                'ar' => 'الخصم: من الأدنى إلى الأعلى',
                'column' => 'products.discount',
                'direction' => 'asc',
            ],
        ];
    }

    public static function keys(): array
    {
        return array_column(self::list(), 'key');
    }

    public static function get(string $key): ?array
    {
        foreach (self::list() as $option) {
            if ($option['key'] === $key) {
                return $option;
            }
        }

        return null;
    }
}
