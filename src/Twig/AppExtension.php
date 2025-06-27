<?php
// src/Twig/AppExtension.php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('unique', [$this, 'uniqueFilter']),
        ];
    }

    public function uniqueFilter($array)
    {
        if (!is_array($array)) {
            return $array;
        }

        return array_unique($array);
    }
}

