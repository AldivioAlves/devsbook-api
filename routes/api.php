<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\SearchController;

Route::get('ping',function (){
   return[
       'pong'=>true
   ];
});

Route::get('/401',[AuthController::class,'unauthorized'])->name('login');

Route::post('/auth/login',[AuthController::class,'login']);
Route::post('/auth/logout',[AuthController::class,'logout']);
Route::post('/auth/refresh',[AuthController::class,'refresh']);

Route::post('/user',[AuthController::class,'create']);
Route::put('/user',[UserController::class,'update']);
Route::post('/user/avatar',[UserController::class,'updateAvatar']);
Route::post('/user/cover',[UserController::class,'updateCover']);

Route::get('/feed',[FeedController::class,'read']);
Route::get('user/feed',[FeedController::class,'userFeed']);
Route::get('user/{id}/feed',[FeedController::class,'userFeed']);

Route::get('/user',[UserController::class,'read']);
Route::get('/user/{id}',[UserController::class,'read']);

Route::post('/feed',[FeedController::class,'create']);

Route::post('/post/{id}',[PostController::class,'like']);
Route::post('/post/{id}/comment',[PostController::class,'comment']);

Route::get('/search',[SearchController::class,'search']);



/** EndPoints
 *   * sem login
 * POST *api/auth/login (email, password)
 * POST api/auth/logout
 * POST api/auth/refresh
 *
 * POST *api/user (name,email,password,birthday)
 * PUT  api/user  (name, email, password, birthday, city, work, password,password_confirm)
 * GET  api/user
 * GET api/user/123
 *
 * POST api/user/avatar (avatar)
 * POST api/user/cover  (cover)
 *
 * GET api/feed          (page)
 * GET api/user/feed     (page)
 * GET api/user/123/feed (page)
 *
 * POST api/feed (type=text/photo, body, photo)
 *
 * POST api/post/123/like
 * POST api/post/123/comment (text)
 *
 * GET api/search/ (text)
 */
