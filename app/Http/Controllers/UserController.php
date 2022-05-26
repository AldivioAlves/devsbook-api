<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Image;

class UserController extends Controller
{
    private $loggedUser;
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->loggedUser=auth()->user();
    }

    public function update(Request $request){
        $result =['error'=>''];

        $name = $request->input('name');
        $email =$request->input('email');
        $birthdate=$request->input('birthdate');
        $city = $request->input('city');
        $work = $request->input('work');
        $password =$request->input('password');
        $password_confirm = $request->input('password_confirm');

        $user =User::find($this->loggedUser['id'])->first();

        if($name){
            $user->name = $name;
        }

        if($email){
            if($email!=$user->email){
                $emailExists = User::where('email',$email)->first();
                if($emailExists){
                    $result['error']='Email já está cadastrado';
                    return $this->sendResponse($result,409);
                }
                $user->email= $email;
            }
        }

        if($birthdate){
            if(strtotime($birthdate)==false){
                $result['error'] ='Data de nascimento inválida';
                return $this->sendResponse($result,400);
            }
            $user->birthdate =$birthdate;
        }

        if($work){
            $user->work = $work;
        }

        if($city){
            $user->city = $city;
        }

        if($password && $password_confirm){
            if($password!=$password_confirm){
                $result['error']='As senhas não batem';
                return $this->sendResponse($result,400);
            }
            $hash =password_hash($password,PASSWORD_DEFAULT);
            $user->password = $hash;
        }

        $user->save();
        $result['user']=$user;
        return $this->sendResponse($result,200);
    }

    public function updateAvatar(Request $request){
        $result = ['error'=>''];

        $allowTypes = ['image/jpeg','image/jpg','image/png'];

        $image = $request->file('avatar');

        if(!$image){
            $result['error'] = 'Imagem não enviada.';
            return $this->sendResponse($result,400);
        }

        if(!in_array($image->getClientMimeType(),$allowTypes)){
            $result['error'] = 'Arquivo não suportado.';
            return $this->sendResponse($result,400);
        }
        $filename = md5(time().rand(0,9999)).'jpg';
        $destPath = public_path('/media/avatars');

        $img = Image::make($image->path())
        ->fit(200,200)
        ->save($destPath.'/'.$filename);

        $user = User::find($this->loggedUser['id']);
        $user->avatar = $filename;
        $user->save();

        $result['url'] = url('/media/avatars/'.$filename);

        return $result;
    }
}
