<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class PostController extends Controller
{
    private $loggedUser;
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->loggedUser=auth()->user();
    }

    public function  like($id){
        $result = ['error'=>''];

        // verificar se o post existe
        $post = Post::find($id);
        if(!$post){
            $result['error'] = 'O post não existe';
            return $this->sendResponse($result,404);
        }
        // verificar se já existe o like ( adicionar ou remover o like)
        $isLiked = PostLike::where('post_id',$id)
            ->where('user_id',$this->loggedUser['id'])->first();

        if($isLiked){
            $isLiked->delete();
            $result['isLiked'] = false;
        }else{
            $newPostLike = new PostLike();
            $newPostLike->post_id = $id;
            $newPostLike->user_id = $this->loggedUser['id'];
            $newPostLike->created_at = date('Y-m-d H:i:s');
            $newPostLike->save();
            $result['isLiked']= true;
        }
        $likeCount = PostLike::where('post_id',$id)->count();
        $result['likeCount']= $likeCount;
        return $this->sendResponse($result,201);
    }

    public function  comment(Request $request, $id){
        $result = ['error'=>''];

        // verificar se o post existe
        $post = Post::find($id);
        if(!$post){
            $result['error'] = 'O post não existe';
            return $this->sendResponse($result,404);
        }

        $comment = $request->input('comment');
        if(!$comment){
            $result['error'] = 'Não foi enviado o comentário';
            return $this->sendResponse($result,400);
        }

        $newPostComment = new PostComment();
        $newPostComment->post_id = $id;
        $newPostComment->user_id = $this->loggedUser['id'];
        $newPostComment->created_at = date('Y-m-d H:i:s');
        $newPostComment->body = $comment;
        $newPostComment->save();

        $result = $newPostComment;
        return $this->sendResponse($result,201);
    }
}
