<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UploadFile;

class HomeController extends Controller
{
    public function __construct()
    {
    }

    public function index() {
        return view('index');
    }
}
