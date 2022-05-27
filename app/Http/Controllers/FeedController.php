<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Image;
use App\Models\User;
use App\Models\UserRelation;
use App\Models\PostLike;
use App\Models\PostComment;

class FeedController extends Controller
{

    private $loggedUser;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
    }

    public function create(Request $request)
    {
        $result = ['error' => ''];

        $type = $request->input('type');
        $body = $request->input('body');
        $photo = $request->file('photo');

        if (!$type) {
            $result['error'] = 'Dados não enviados';
            return $this->sendResponse($result, 400);
        }
        switch ($type) {
            case 'text':
                if (!$body) {
                    $result['error'] = 'Texto não enviado';
                    return $this->sendResponse($result, 400);
                }
                break;
            case 'photo':
                $allowTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                if (!in_array($photo->getClientMimeType(), $allowTypes)) {
                    $result['error'] = 'Arquivo não suportado.';
                    return $this->sendResponse($result, 400);
                }

                $filename = md5(time() . rand(0, 9999)) . '.jpg';
                $desPath = public_path('/media/uploads');
                Image::make($photo->path())
                    ->resize(800, null, function ($constraint) {
                        $constraint->aspectRatio(); //mantendo a proporção
                    })
                    ->save($desPath . '/' . $filename);
                $body = $filename;

                break;
            default:
                $result['error'] = 'Tipo de postagem inexistente';
                return $this->sendResponse($result, 400);
        }
        if ($body) {
            $post = new Post();
            $post->user_id = $this->loggedUser['id'];
            $post->type = $type;
            $post->created_at = date('Y-m-d H:i:s');
            $post->body = $body;
            $post->save();
        }

        $result = $post;
        return $this->sendResponse($result, 201);
    }

    public function read(Request $request)
    {
        $result = ['error' => ''];

        $page = intval($request->input('page'));
        $perPage = 2;

        // recuperar usuários que o usuario logado segue (incluindo o mesmo)

        $users = [];
        $userList = UserRelation::where('user_from', $this->loggedUser['id'])->get(); // o proprio usuario

        foreach ($userList as $userItem) {
            $users[] = $userItem['user_to']; // ids dos usuários que o usuario logado segue
            $users[] = $this->loggedUser['id'];
        }

        // recuperar posts ordenados pela data de criação (desc)

        $postList = Post::whereIn('user_id', $users)
            ->orderBy('created_at', 'desc')
            ->offset($page * $perPage)
            ->limit($perPage)
            ->get();

        $total = Post::whereIn('user_id', $users)->count();
        $pageCount = ceil($total / $perPage);

        // incluir informações adcionais nas postagens ( se o post é do user logado ,info dos usuários, likes, lista de comentarios)


        $result['posts'] = $this->postListToObject($postList, $this->loggedUser['id']);
        $result['pageCount'] = $pageCount;
        $result['currentPage'] = $page;
        return $this->sendResponse($result, 200);
    }

    protected  function postListToObject($postList, $loggedId)
    {
        foreach ($postList as $postKey => $postItem) {

            if ($postItem['user_id'] == $loggedId) {
                $postList[$postKey]['mine'] = true;
            } else {
                $postList[$postKey]['mine'] = false;
            }

            $userInfo = User::find($postItem['user_id']);
            $userInfo['avatar'] = url('media/avatars/' . $userInfo['avatar']);
            $userInfo['cover'] = url('media/covers/' . $userInfo['cover']);
            $postList[$postKey]['user'] = $userInfo;

            $likes = PostLike::where('post_id', $postItem['id'])->count();
            $postList[$postKey]['likeCount'] = $likes;
            $isLiked = PostLike::where('post_id', $postItem['id'])
                ->where('user_id', $loggedId)
                ->count();
            $postList[$postKey]['liked'] = $isLiked > 0;

            $comments = PostComment::where('post_id', $postItem['id'])->get();
            foreach ($comments as $commentKey => $comment) {
                $user = User::find($comment['user_id']);
                $user['avatar'] = url('media/avatars/' . $userInfo['avatar']);
                $user['cover'] = url('media/covers/' . $userInfo['cover']);
                $comments[$commentKey]['user'] = $user;
            }
            $postList[$postKey]['comments']= $comments;
        }

        return $postList;
    }

}
