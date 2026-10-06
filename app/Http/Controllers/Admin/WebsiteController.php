<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class WebsiteController extends Controller
{
    public function edit()
    {
        $settings = Setting::values();

        return view('admin.website.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'site_name' => 'required|string|max:200',
            'site_description' => 'nullable|string|max:500',
            'site_email' => 'nullable|email|max:200',
            'site_phone' => 'nullable|string|max:50',
            'site_address' => 'nullable|string|max:500',
            'site_banner' => 'nullable|string|max:300',
            'site_banner_title' => 'nullable|string|max:120',
            'site_banner_subtitle' => 'nullable|string|max:200',
            'wa_number' => 'nullable|string|max:30',
            'instagram_url' => 'nullable|url|max:300',
            'facebook_url' => 'nullable|url|max:300',
            'youtube_url' => 'nullable|url|max:300',
            'tokopedia_url' => 'nullable|url|max:300',
            'shopee_url' => 'nullable|url|max:300',
            'lazada_url' => 'nullable|url|max:300',
            'blibli_url' => 'nullable|url|max:300',
            'tiktok_shop_url' => 'nullable|url|max:300',
            'shipping_origin_city' => 'nullable|string|max:100',
            'shipping_origin_province' => 'nullable|string|max:100',
            'shipping_origin_postal' => 'nullable|string|max:10',
            'shipping_origin_id' => 'nullable|string|max:20',
            'shipping_couriers' => 'nullable|string|max:100',
            'shipping_regular_cost' => 'required|numeric|min:0|max:10000000',
            'shipping_instant_cost' => 'required|numeric|min:0|max:10000000',
            'shipping_instant_enabled' => 'required|in:0,1',
            'shipping_instant_areas' => 'nullable|string|max:300',
            'admin_fee' => 'required|numeric|min:0|max:10000000',
            'admin_fee_label' => 'nullable|string|max:50',
            'feature_reviews' => 'required|in:0,1',
            'feature_wishlist' => 'required|in:0,1',
            'feature_live_chat' => 'required|in:0,1',
            'feature_cod' => 'required|in:0,1',
        ]);

        Setting::putMany($data);

        return redirect()->route('admin.website.edit')->with('success', 'Pengaturan website berhasil disimpan.');
    }
}
