<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function getFavicon()
    {
        $favicon = Setting::where('key', 'favicon')->first();

        return response()->json([
            'favicon' => $favicon ? asset('storage/'.$favicon->value) : null,
        ]);
    }
    public function getSiteSettings()
    {
        $siteName = Setting::where('key', 'site_name')->first();
        $siteUrl = Setting::where('key', 'site_url')->first();
        $logo = Setting::where('key', 'logo')->first();
        $favicon = Setting::where('key', 'favicon')->first();
        $menuLinks = Setting::where('key', 'menu_links')->first();

        return response()->json([
            'site_name' => $siteName ? $siteName->value : null,
            'site_url' => $siteUrl ? $siteUrl->value : null,
            'logo' => $logo ? $logo->value : null,
            'favicon' => $favicon ? $favicon->value : null,
            'menu_links' => $menuLinks ? json_decode($menuLinks->value, true) : [],
        ]);
    }

    public function updateSiteSettings(Request $request)
    {
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $path = $file->store('settings', 'public');
            Setting::updateOrCreate(['key' => 'logo'], ['value' => $path]);
        }

        if ($request->hasFile('favicon')) {
            $file = $request->file('favicon');
            $path = $file->store('settings', 'public');
            Setting::updateOrCreate(['key' => 'favicon'], ['value' => $path]);
        }

        if ($request->has('site_name')) {
            Setting::updateOrCreate(['key' => 'site_name'], ['value' => $request->input('site_name')]);
        }

        if ($request->has('site_url')) {
            Setting::updateOrCreate(['key' => 'site_url'], ['value' => $request->input('site_url')]);
        }

        return response()->json(['status' => 'success']);
    }

    public function deleteSiteSetting(Request $request)
    {
        $key = $request->input('key');
        if (! $key) {
            return response()->json(['status' => 'error', 'message' => 'Key is required'], 400);
        }

        $validKeys = ['logo', 'favicon', 'site_name', 'site_url', 'menu_links'];
        if (! in_array($key, $validKeys)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid key'], 400);
        }

        Setting::where('key', $key)->delete();

        return response()->json(['status' => 'success']);
    }

    public function getMenuLinks()
    {
        $menuLinks = Setting::where('key', 'menu_links')->first();

        return response()->json([
            'menu_links' => $menuLinks ? json_decode($menuLinks->value, true) : [],
        ]);
    }

    public function updateMenuLinks(Request $request)
    {
        $menuLinks = $request->input('menu_links');

        if (! $menuLinks) {
            $raw = json_decode($request->getContent(), true);
            if (is_array($raw)) {
                $menuLinks = $raw;
            }
        }

        if ($menuLinks && is_array($menuLinks)) {
            Setting::updateOrCreate(['key' => 'menu_links'], ['value' => json_encode($menuLinks)]);
        }

        return response()->json(['status' => 'success']);
    }

    public function updateSocialLinks(Request $request)
    {
        $facebook = $request->input('facebook');
        $instagram = $request->input('instagram');
        $linkedin = $request->input('linkedin');

        if ($facebook) {
            Setting::updateOrCreate(['key' => 'facebook'], ['value' => $facebook]);
        }
        if ($instagram) {
            Setting::updateOrCreate(['key' => 'instagram'], ['value' => $instagram]);
        }
        if ($linkedin) {
            Setting::updateOrCreate(['key' => 'linkedin'], ['value' => $linkedin]);
        }

        return response()->json(['status' => 'success']);
    }

    public function getSocialLinks()
    {
        $facebook = Setting::where('key', 'facebook')->first();
        $instagram = Setting::where('key', 'instagram')->first();
        $linkedin = Setting::where('key', 'linkedin')->first();

        return response()->json([
            'facebook' => $facebook ? $facebook->value : null,
            'instagram' => $instagram ? $instagram->value : null,
            'linkedin' => $linkedin ? $linkedin->value : null,
        ]);
    }

    public function deleteSocialLinks(Request $request)
    {
        $keys = $request->input('keys', []);
        if (empty($keys)) {
            return response()->json(['status' => 'success']);
        }
        Setting::whereIn('key', $keys)->delete();

        return response()->json(['status' => 'success']);
    }

    public function getSchema()
    {
        // 1. Get all settings in one query to save database resources
        $settings = Setting::whereIn('key', [
            'site_name',
            'site_url',
            'logo',
            'facebook',
            'instagram',
            'linkedin',
        ])->pluck('value', 'key');

        $siteUrl = rtrim($settings->get('site_url'), '/');
        $siteName = $settings->get('site_name');

        // 2. Build social links
        $sameAs = array_values(array_filter([
            $settings->get('facebook'),
            $settings->get('instagram'),
            $settings->get('linkedin'),
        ]));

        // 3. Build the Schema array
        $schema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => $siteUrl.'/#organization',
                    'name' => $siteName,
                    'url' => $siteUrl,
                    'sameAs' => $sameAs,
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $siteUrl.'/#website',
                    'url' => $siteUrl,
                    'name' => $siteName,
                    'publisher' => [
                        '@id' => $siteUrl.'/#organization',
                    ]
                ],
            ],
        ];

        // 4. Only add logo if it exists (prevents "logo": null)
        if ($settings->get('logo')) {
            $schema['@graph'][0]['logo'] = [
                '@type' => 'ImageObject',
                'url' => asset('storage/'.$settings->get('logo')),
            ];
        }

        // Return the array to be used in a Blade view
        return $schema;
    }
}
