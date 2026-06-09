<?php

namespace App\Http\Controllers;

use App\Models\Setting;

class SiteSettingController extends Controller
{
    public function index()
    {
        $keys = ['support_phone', 'support_text', 'mega_menu_banner_enabled'];
        $settings = Setting::whereIn('key', $keys)->pluck('value', 'key');

        return response()->json([
            'success' => true,
            'data' => [
                'support_phone' => $settings['support_phone'] ?? '1900 - 888',
                'support_text' => $settings['support_text'] ?? 'Need help? Call Us:',
                'mega_menu_banner_enabled' => filter_var($settings['mega_menu_banner_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ],
        ]);
    }
}
