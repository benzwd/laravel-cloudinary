<?php

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-upload', function () {
    $token = csrf_token();

    return <<<HTML
    <form method="POST" action="/test-upload" enctype="multipart/form-data">
        <input type="hidden" name="_token" value="{$token}">
        <input type="file" name="image" required>
        <button type="submit">Upload to articles/test-image</button>
    </form>
    HTML;
});

Route::post('/test-upload', function (Request $request) {
    $request->validate(['image' => 'required|image']);

    /** @var FilesystemAdapter $disk */
    $disk = Storage::disk('cloudinary');

    $disk->putFileAs('properties', $request->file('image'), $request->file('image')->getClientOriginalName());

    return 'Upload successful (check your Cloudinary dashboard to confirm).';
});

Route::get('/test-delete', function () {
    Storage::disk('cloudinary')->delete('properties/test-image');

    return 'Deleted (check your Cloudinary dashboard to confirm).';
});

Route::get('/test-delete-directory', function () {
    Storage::disk('cloudinary')->deleteDirectory('properties');

    return 'Directory delete attempted (check your Cloudinary dashboard — this exercises the Admin API bulk-delete path).';
});
