<?php

use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Storage;

if (!function_exists('uploadFile')) {
    function uploadFile($file, $path)
    {
        $name = uniqid() . '.' . $file->getClientOriginalExtension();
        $destinationPath = public_path('') . $path;
        $file->move($destinationPath, $name);
        return $name;
    }
}



if (!function_exists('diffForHumans')) {
    function diffForHumans($date)
    {
        return Carbon::parse($date)->diffForHumans();
    }
}

if (!function_exists('formatMDY')) {
    /**
     * @param $argument
     * @return string
     */
    function formatMDY($argument)
    {
        return Carbon::parse($argument)->format('m.d.Y');
    }
}



if (!function_exists('SingleImageUploadHandler')) {
    function SingleImageUploadHandler($request, $filename, $uploadedFile = '', $uniqueKey = '', $location = '')
    {
        $fileNameToStore = $request->hasFile($uploadedFile);
        if ($request->hasFile($uploadedFile)) {
            //Get file from client side
            $file = $request->file($uploadedFile);
            $extension = $file->getClientOriginalExtension();
            $fileFormat = strtolower('/' . $filename . '-' . $uniqueKey) . '.' . $extension;
            $fileNameToStore = '/upload' . $location . str_replace(' ', '-', $fileFormat);

            // Store in Storage Filesystem
            Storage::disk('local_public')->putFileAs($location, $file, $fileFormat);
        }
        return $fileNameToStore;
    }
}


if (!function_exists('SingleImageUpdateHandler')) {
    function SingleImageUpdateHandler($request, $filename, $dbFilename, $uploadedFile = '', $uniqueKey = '', $location = '')
    {
        $fileNameToStore = $dbFilename;
        if ($request->hasFile($uploadedFile)) {
            // delete old image first
            if (Storage::disk('local_public')->exists($dbFilename)) {
                Storage::disk('local_public')->delete($dbFilename);
            }

            //Get file from client side
            $file = $request->file($uploadedFile);
            $extension = $file->getClientOriginalExtension();
            $fileFormat = strtolower('/' . $filename . '-' . $uniqueKey) . '.' . $extension;
            $fileNameToStore = '/upload' . $location . str_replace(' ', '-', $fileFormat);

            // Store in Storage Filesystem
            Storage::disk('local_public')->putFileAs($location, $file, $fileFormat);
        }
        return $fileNameToStore;
    }
}

// check active auth guard
if (!function_exists('activeGuard')) {
    function activeGuard()
    {
        foreach (array_keys(config('auth.guards')) as $guard) {
            if (auth()->guard($guard)->check()) {
                return $guard;
            }
        }
        return null;
    }
}


/**
 * Custom encryption for javascript
 *
 * This function will return encrypted data with key pair
 */
if (!function_exists('basicEncrypt')) {
    /**
     * @param $data
     * @return array
     */
    function basicEncrypt($data): string
    {
        return encrypt($data);
    }
}
if (!function_exists('basicDecrypt')) {
    /**
     * @param $encrypted
     * @return string
     */
    function basicDecrypt($encrypted): string
    {
        try {
            return decrypt($encrypted);
        } catch (DecryptException $e) {
            return "";
        }
    }
}

if (!function_exists('createFromFormatCustom')) {
    function createFromFormatCustom(&$date, $formatting = false): mixed
    {
        try {
            $date = $formatting
                ? Carbon::createFromFormat(config('app.to_date_format'), $date)->format(config('app.to_date_format'))
                : Carbon::createFromFormat(config('app.to_date_format'), $date)->toDateString();
        } catch (Exception $exception) {
            // reportLog($exception);
        }
        return $date;
    }
}
