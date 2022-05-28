<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    private $loggedUser;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
    }

    public function search(Request $request)
    {
        $result = [
            'error' => '',
            'users' => []
        ];
        $param = $request->input('param');
        if (!$param) {
            $result['error'] = 'Não foi enviado nenhum parametro para a busca';
            return $this->sendResponse($result, 400);
        }
        $users = User::where('name', 'like', '%' . $param . '%')->get();
        foreach ($users as $user) {
            $result['users'][] = [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar
            ];
        }
        return $this->sendResponse($result, 200);

    }
}
