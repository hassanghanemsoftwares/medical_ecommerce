<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\ConfigurationRequest;
use App\Http\Resources\V1\Admin\ConfigurationResource;
use App\Models\Configuration;
use Exception;
use Illuminate\Support\Facades\DB;

class ConfigurationController extends Controller
{
    public function index()
    {
        try {
            $configs = Configuration::all();
            return response()->json([
                'result' => true,
                'message' => __('messages.configuration.configuration_fetched'),
                'configurations' => ConfigurationResource::collection($configs),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(__('messages.configuration.failed_to_fetch_configuration'), $e);
        }
    }

    public function update(ConfigurationRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            foreach ($data as $key => $value) {
                $config = Configuration::where('key', $key)->first();

                if ($key === 'about_us') {
                    // Merge existing about_us config if exists
                    $aboutUs = $config ? $config->value : [];

                    // Update title/description
                    $aboutUs['title'] = $value['title'] ?? $aboutUs['title'] ?? [];
                    $aboutUs['description'] = $value['description'] ?? $aboutUs['description'] ?? [];

                    // Check for image upload
                    if ($request->hasFile('about_us.image')) {
                        // Delete old image
                        if (!empty($aboutUs['image'])) {
                            Configuration::deleteImage($aboutUs['image']);
                        }

                        // Store new image
                        $aboutUs['image'] = Configuration::storeImage($request->file('about_us.image'));
                    }

                    $config ? $config->update(['value' => $aboutUs])
                        : Configuration::create(['key' => $key, 'value' => $aboutUs]);
                } else {
                    // Regular config update
                    if ($config) {
                        $config->update(['value' => $value]);
                    } else {
                        Configuration::create(['key' => $key, 'value' => $value]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __('messages.configuration.configuration_updated'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse(__('messages.configuration.failed_to_update_configuration'), $e);
        }
    }
}
