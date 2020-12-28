<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|


*/
use App\Api;
use Illuminate\Http\Request;
//this is a comment
Route::get('/', function () {
    return view('welcome');
});
Route::get('/privacy_policy', function () {
    return view('about');
});

Route::post('mainwebhook', 'ApiController@mainWebHook');
Route::post('clientwebhook/{token}', 'ApiController@clientWebHook');
Route::post('retailClientWebHook/{token}', 'ApiController@retailClientWebHook');
Route::get('instagram/', 'instaController@instagram');

Route::get('makeSubscription/', 'instaController@makeSubscription');
Route::get('instaCallback/', 'instaController@callBack');
Route::post('instaCallback/', 'instaController@subscriptionNotification');

Route::get('storage/{filename}', function ($filename)
{
    $path = public_path('images/'.$filename);

    if (!File::exists($path)) {
        abort(404);
    }

    $file = File::get($path);
    $type = File::mimeType($path);

    $response = Response::make($file, 200);
    $response->header("Content-Type", $type);

    return $response;
});

Route::get('/token_tutorial', function ()
{
    $path = public_path('images/tutorial.mp4');

    if (!File::exists($path)) {
        abort(404);
    }

    $file = File::get($path);
    $type = File::mimeType($path);

    $response = Response::make($file, 200);
    $response->header("Content-Type", $type);

    return $response;
});
Route::get('/screencast', function ()
{
    $path = public_path('finalVideo.mp4');

    if (!File::exists($path)) {
        abort(404);
    }

    $file = File::get($path);
    $type = File::mimeType($path);

    $response = Response::make($file, 200);
    $response->header("Content-Type", $type);

    return $response;
});
Route::post('prototype', 'ApiController@prototype');


