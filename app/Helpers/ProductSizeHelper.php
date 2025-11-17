<?php

namespace App\Helpers;

class ProductSizeHelper
{
    /**
     * Get available sizes based on category type
     * Returns standard sizes for women, men, or kids categories
     */
    public static function getAvailableSizesForCategory($categoryName = null)
    {
        $categoryName = strtolower($categoryName ?? '');
        
        // Kids sizes (toddler and youth)
        if (str_contains($categoryName, 'kid') || str_contains($categoryName, 'child') || str_contains($categoryName, 'toddler')) {
            return [
                '2T', '3T', '4T', '5T', '6T', // Toddler
                'XS', 'S', 'M', 'L', 'XL',    // Youth
                '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '16' // Numeric kids sizes
            ];
        }
        
        // Women sizes
        if (str_contains($categoryName, 'women') || str_contains($categoryName, 'woman') || str_contains($categoryName, 'ladies')) {
            return [
                'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL',
                '0', '2', '4', '6', '8', '10', '12', '14', '16', '18', '20', '22', '24' // Numeric sizes
            ];
        }
        
        // Men sizes
        if (str_contains($categoryName, 'men') || str_contains($categoryName, 'man') || str_contains($categoryName, 'gentlemen')) {
            return [
                'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL',
                '28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50', '52' // Waist sizes
            ];
        }
        
        // Default sizes (generic)
        return [
            'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'
        ];
    }
}

