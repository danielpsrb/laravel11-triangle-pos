<?php

namespace Modules\Upload\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Modules\Upload\Entities\Upload;

class UploadController extends Controller
{
    protected ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }

    public function filepondUpload(Request $request) {
        $request->validate([
            'image' => 'required|image|mimes:png,jpeg,jpg'
        ]);

        if ($request->hasFile('image')) {
            $uploaded_file = $request->file('image');
            $filename = now()->timestamp . '.' . $uploaded_file->getClientOriginalExtension();
            $folder = uniqid() . '-' . now()->timestamp;

            //using the image manager that has been declared in the constructor
            $file = $this->imageManager->read($uploaded_file->getRealPath());

            // Encode image to JPEG format
            $encoded = $file->toJpg();

            Storage::put('temp/' . $folder . '/' . $filename, $encoded);

            Upload::create([
                'folder'   => $folder,
                'filename' => $filename
            ]);

            return $folder;
        }

        return false;
    }

    public function filepondDelete(Request $request) {
        $upload = Upload::where('folder', $request->getContent())->first();

        Storage::deleteDirectory('temp/' . $upload->folder);
        $upload->delete();

        return response(null);
    }

    public function dropzoneUpload(Request $request) {
        $file = $request->file('file');

        $filename = now()->timestamp . '.' . trim($file->getClientOriginalExtension());

        Storage::putFileAs('temp/dropzone/', $file, $filename);

        return response()->json([
            'name'          => $filename,
            'original_name' => $file->getClientOriginalName(),
        ]);
    }

    public function dropzoneDelete(Request $request) {
        Storage::delete('temp/dropzone/' . $request->file_name);

        return response()->json($request->file_name, 200);
    }
}
