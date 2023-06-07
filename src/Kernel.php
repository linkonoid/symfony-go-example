<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\ErrorHandler\ErrorHandler;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot()
    {
        parent::boot();
        ErrorHandler::register(null, false)->setLoggers([
            \E_DEPRECATED => [null],
            \E_USER_DEPRECATED => [null],
        ]);
    }
}
