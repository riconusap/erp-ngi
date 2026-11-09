<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'GreenlyConnect API Documentation',
    description: 'Dokumentasi RESTful API GreenlyConnect untuk PT Naturally Green Indonesia - Sistem Manajemen Kepegawaian, Master Data, & HRMS ERP',
    contact: new OA\Contact(
        name: 'Naturally Green Indonesia Engineering Team',
        email: 'tech@naturallygreen.id'
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

