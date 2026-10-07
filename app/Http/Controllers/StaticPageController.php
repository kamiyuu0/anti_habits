<?php

namespace App\Http\Controllers;

class StaticPageController extends Controller
{
    public function top()
    {
        return view('static_pages.top');
    }

    public function terms()
    {
        return view('static_pages.terms');
    }

    public function privacy()
    {
        return view('static_pages.privacy');
    }
}
