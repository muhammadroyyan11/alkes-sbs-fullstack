<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WebsiteController extends Controller
{
    public function edit()
    {
        $settings = Cache::get('website_settings', [
            'site_name' => 'ALKES SBS',
            'site_description' => 'Sistem Bisnis Alat Kesehatan',
            'site_email' => '',
            'site_phone' => '',
            'site_address' => '',
        ]);

        return view('admin.website.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'site_name' => 'required|string|max:200',
            'site_description' => 'nullable|string|max:500',
            'site_email' => 'nullable|email|max:200',
            'site_phone' => 'nullable|string|max:50',
            'site_address' => 'nullable|string|max:500',
        ]);

        $settings = $request->only([
            'site_name', 'site_description', 'site_email',
            'site_phone', 'site_address',
        ]);

        Cache::put('website_settings', $settings);

        return redirect()->route('admin.website.edit')->with('success', 'Pengaturan website berhasil disimpan.');
    }
}
