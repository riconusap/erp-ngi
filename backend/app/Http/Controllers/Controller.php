<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'KBYN Admin API Documentation',
    description: 'Dokumentasi RESTful API untuk Sistem Manajemen Kepegawaian, Master Data, & Modul HRMS KBYN',
    contact: new OA\Contact(
        name: 'KBYN Engineering Team',
        email: 'admin@kbyn.id'
    )
)]
#[OA\Server(
    url: '/api',
    description: 'API Server'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Masukkan token Bearer yang diperoleh setelah login (contoh: Bearer eyJhbGciOi...)'
)]
abstract class Controller
{
    //
}

