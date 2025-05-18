<?php
namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ConfigurationRequest;
use App\Http\Resources\V1\ConfigurationResource;
use App\Models\Configuration;
use Illuminate\Http\Request;
use Exception;

class ConfigurationController extends Controller
{
    public function index(Request $request)
    {
        try {
            $configs = Configuration::all();
            return response()->json([
                'result' => true,
                'message' => __('messages.configuration.configuration_fetched'),
                'configurations' => ConfigurationResource::collection($configs),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('messages.configuration.failed_to_fetch_configuration', $e);
        }
    }

    public function update(ConfigurationRequest $request)
    {
        try {
            $data = $request->validated();
            
            foreach ($data as $key => $value) {
                $config = Configuration::where('key', $key)->first();
                if ($config) {
                    $config->update(['value' => $value]);
                }
            }

            return response()->json([
                'result' => true,
                'message' => __('messages.configuration.configuration_updated'),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('messages.configuration.failed_to_update_configuration', $e);
        }
    }


}
