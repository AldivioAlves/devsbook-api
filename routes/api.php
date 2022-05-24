<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('ping',function (){
   return[
       'pong'=>true
   ];
});

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
