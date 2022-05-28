<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Models\UserRelation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Image;

class UserController extends Controller
{
    private $loggedUser;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
    }

    public function update(Request $request)
    {
        $result = ['error' => ''];

        $name = $request->input('name');
        $email = $request->input('email');
        $birthdate = $request->input('birthdate');
        $city = $request->input('city');
        $work = $request->input('work');
        $password = $request->input('password');
        $password_confirm = $request->input('password_confirm');

        $user = User::find($this->loggedUser['id'])->first();

        if ($name) {
            $user->name = $name;
        }

        if ($email) {
            if ($email != $user->email) {
                $emailExists = User::where('email', $email)->first();
                if ($emailExists) {
                    $result['error'] = 'Email já está cadastrado';
                    return $this->sendResponse($result, 409);
                }
                $user->email = $email;
            }
        }

        if ($birthdate) {
            if (strtotime($birthdate) == false) {
                $result['error'] = 'Data de nascimento inválida';
                return $this->sendResponse($result, 400);
            }
            $user->birthdate = $birthdate;
        }

        if ($work) {
            $user->work = $work;
        }

        if ($city) {
            $user->city = $city;
        }

        if ($password && $password_confirm) {
            if ($password != $password_confirm) {
                $result['error'] = 'As senhas não batem';
                return $this->sendResponse($result, 400);
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $user->password = $hash;
        }

        $user->save();
        $result['user'] = $user;
        return $this->sendResponse($result, 200);
    }

    public function updateAvatar(Request $request)
    {
        $result = ['error' => ''];

        $allowTypes = ['image/jpeg', 'image/jpg', 'image/png'];

        $image = $request->file('avatar');

        if (!$image) {
            $result['error'] = 'Imagem não enviada.';
            return $this->sendResponse($result, 400);
        }

        if (!in_array($image->getClientMimeType(), $allowTypes)) {
            $result['error'] = 'Arquivo não suportado.';
            return $this->sendResponse($result, 400);
        }
        $filename = md5(time() . rand(0, 9999)) . '.jpg';
        $destPath = public_path('/media/avatars');

        $img = Image::make($image->path())
            ->fit(200, 200)
            ->save($destPath . '/' . $filename);

        $user = User::find($this->loggedUser['id']);
        $user->avatar = $filename;
        $user->save();

        $result['url'] = url('/media/avatars/' . $filename);

        return $result;
    }

    public function updateCover(Request $request)
    {
        $result = ['error' => ''];

        $allowTypes = ['image/jpeg', 'image/jpg', 'image/png'];

        $image = $request->file('cover');

        if (!$image) {
            $result['error'] = 'Imagem não enviada.';
            return $this->sendResponse($result, 400);
        }

        if (!in_array($image->getClientMimeType(), $allowTypes)) {
            $result['error'] = 'Arquivo não suportado.';
            return $this->sendResponse($result, 400);
        }
        $filename = md5(time() . rand(0, 9999)) . '.jpg';
        $destPath = public_path('/media/covers');

        $img = Image::make($image->path())
            ->fit(850, 310)
            ->save($destPath . '/' . $filename);

        $user = User::find($this->loggedUser['id']);
        $user->cover = $filename;
        $user->save();

        $result['url'] = url('/media/covers/' . $filename);

        return $result;
    }

    public function read($id = false)
    {
        $result = ['error' => ''];
        if (!$id) {
            $info = $this->loggedUser;
        } else {
            $info = User::find($id);
            if (!$info) {
                $result['error'] = 'Usuário inexistente';
                return $this->sendResponse($result, 404);
            }
        }
        $info['avatar'] = url('media/avatars/' . $info['avatar']);
        $info['cover'] = url('media/covers/' . $info['cover']);

        $info['me'] = $info['id'] == $this->loggedUser['id'];

        $dateFrom  = new \DateTime($info['birthdate']);
        $dateTo = new \DateTime('today');
        $info['age'] = $dateFrom->diff($dateTo)->y;

        $info['followers']=UserRelation::where('user_to', $info['id'])->count();
        $info['following'] = UserRelation::where('user_from', $info['id'])->count();

        $info['photoCount'] = Post::where('user_id',$info['id'])
            ->where('type','photo')
            ->count();

        $hasRelation = UserRelation::where('user_from',$this->loggedUser['id'])
            ->where('user_to',$info['id'])
            ->first();
        $info['isFollowing'] = $hasRelation!=null;

        $result = $info;

        return $this->sendResponse($result, 200);
    }

    public function follow($id){
        $result = ['error'=>''];

        if($id == $this->loggedUser['id']){
            $result['error'] ='Você não pode seguir você mesmo';
            return $this->sendResponse($result,400);
        }
        $userExists = User::find($id);
        if(!$userExists){
            $result['error'] = 'usuário não existe';
            return $this->sendResponse($result,404);
        }

        $relation = UserRelation::where('user_from',$this->loggedUser['id'])
            ->where('user_to',$id)->first();
        if($relation){
            $relation->delete();
        }else{
            $newRelation = new UserRelation();
            $newRelation->user_from= $this->loggedUser['id'];
            $newRelation->user_to = $id;
            $newRelation->save();
        }
        return $this->sendResponse($result,201);
    }

    public function followers($id){
        $result = ['error'=>''];
        $userExists = User::find($id);
        if(!$userExists){
            $result['error'] = 'usuário não existe';
            return $this->sendResponse($result,404);
        }
        $followers = UserRelation::where('user_to',$id)->get();
        $following = UserRelation::where('user_from',$this->loggedUser['id'])->get();
        $result['followers'] = [];
        $result['following'] = [];

        foreach ($followers as $item){
            $user = User::find($item->user_from);
            $result['followers'][]=[
              'id'=>$user->id,
              'name'=>$user->name,
              'avatar'=>url('media/avatars/'.$user['avatar'])
            ];
        }

        foreach ($following as $item){
            $userFollowing = User::find($item->user_from);
          //  dd($userFollowing->id);
            $result['following'][]=[
                'id'=>$userFollowing->id,
                'name'=>$userFollowing->name,
                'avatar'=>url('media/avatars/'.$userFollowing->avatar)
            ];
        }

        return $this->sendResponse($result,200);
    }

    public function photos(){
        $result = ['error'=>''];
        return $this->sendResponse($result,200);
    }
}
