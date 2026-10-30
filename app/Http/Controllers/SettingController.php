<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function edit()
    {
        $settings = Setting::current();

        return view('admin.settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $settings = Setting::current();

        $data = $request->validate([
            'shop_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'currency' => ['required', 'string', 'max:10'],
            'receipt_header' => ['nullable', 'string'],
            'receipt_footer' => ['nullable', 'string'],
            'low_stock_alert' => ['required', 'integer', 'min:0'],
            'expiration_alert_days' => ['required', 'integer', 'min:1'],
            'receipt_width' => ['required', 'in:58mm,80mm'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('logo')) {
            if ($settings->logo) {
                Storage::disk('public')->delete($settings->logo);
            }
            $data['logo'] = $request->file('logo')->store('settings', 'public');
        }

        $settings->update($data);

        return back()->with(['message' => 'Settings saved', 'alert-type' => 'success']);
    }
}
