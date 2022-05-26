<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', [
            'except' => [
                'login',
                'create',
                'unauthorized'
            ]
        ]);
    }

    public function unauthorized(){
        return $this->sendResponse(['error'=>'não autorizado'],401);
    }

    public function create(Request $request){
        $result = ['error'=>''];

        $name = $request->input('name');
        $email  = $request->input('email');
        $password= $request->input('password');
        $birthdate= $request->input('birthdate');
        if($name&&$email&&$password&&$birthdate&&$email){

            if(strtotime($birthdate)===false){
                $result['error']='Data de nascimento inválida';
                return $this->sendResponse($result,400);
            }
            $emailExists = User::where('email',$email)->count();
            if($emailExists>0){
                $result['error']='Email já cadastrado';
                return $this->sendResponse($result,409);
            }

            $hash = password_hash($password,PASSWORD_DEFAULT);
            $newUser = new User();
            $newUser->name= $name;
            $newUser->password = $hash;
            $newUser->email =$email;
            $newUser->birthdate =$birthdate;
            $newUser->save();

            $token = auth()->attempt([
               'email'=>$email,
               'password'=>$password
            ]);
            if(!$token) {
                $result['error'] = 'Ocorreu um erro desconhecido';
                return $this->sendResponse($result,500);
            }
            $result['token'] = $token;
            return $this->sendResponse($result,201);
        }else{
            $result['error'] ='não enviou todos os campos';
            return $this->sendResponse($result,400);
        }
    }

    public function login(Request $request){
        $result =['error'=>''];
        $email = $request->input('email');
        $password =$request->input('password');
        if(!$email||!$password){
            $result['error']='Email ou senha não enviados.';
            return $this->sendResponse($result,400);
        }

        $token = auth()->attempt([
           'email'=>$email,
           'password'=>$password
        ]);
        if(!$token){
            $result['error']='email ou senha errado';
            return $this->sendResponse($result,401);
        }
        $result['token']=$token;

        return $this->sendResponse($result,200);
    }

    public function logout(Request $request){
        \auth()->logout();
        return $this->sendResponse(['error'=>''],200);
    }

    public function  refresh(Request $request){
        $token = \auth()->refresh();
        return $this->sendResponse([
            'error'=>'',
            'token'=>$token
        ],200);
    }
}
