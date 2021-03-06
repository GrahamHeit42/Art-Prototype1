<?php

namespace App\Http\Controllers\Frontend;

use Carbon\Carbon;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use App\Models\Image;
use App\Models\Follow;
use App\Models\Medium;
use App\Models\Comment;
use App\Models\Subject;
use App\Models\PostView;
use App\Models\Username;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class PostController extends Controller
{
    /*public function index(): JsonResponse
    {
        view()->share('page_title', 'Posts');
        $posts = Post::latest()->limit(30)->with('images')->get();

        return response()->json([
            'status' => TRUE,
            'posts' => $posts
        ]);
    }*/

    public function create($type = NULL)
    {
        view()->share('page_title', 'Create Post');
        $subjects = Subject::whereStatus(1)->get();
        $mediums = Medium::whereStatus(1)->get();
        $usernames = Username::where('user_id', '!=', auth()->user()->id)->get();

        return view('frontend.posts.create', compact('type', 'subjects', 'mediums', 'usernames'));
    }

    public function store(Request $request)
    {
        if ($request->ajax()) {
            $request->validate([
                'type' => 'required_if:id,null',
                'subject_id' => 'required',
                'medium_id' => 'required',
                'title' => 'required_if:id,null',
                'description' => 'required_if:id,null',
            ]);
            // dd($request->post('type'));
            // if($request->)
            if ($request->post('type') === config('constants.Commissioner')) {
                if ($request->post("price") || $request->post("speed") || $request->post("communication") || $request->post("quality") || $request->post("professionalism")) {
                    $request->validate([
                        'price' => 'required',
                        'speed' => 'required',
                        'communication' => 'required',
                        'quality' => 'required',
                        'professionalism' => 'required',
                    ]);
                }
            }
            if ($request->post('type') === config('constants.Commisioned')) {
                if ($request->post("transaction") || $request->post("speed") || $request->post("communication") || $request->post("concept")) {
                    $request->validate([
                        'transaction' => 'required',
                        'speed' => 'required',
                        'communication' => 'required',
                        'concept' => 'required',
                    ]);
                }
            }
            $postId = $request->post('id') ?? NULL;
            $postType = $request->post('type');

            if (is_null($postId)) {
                $request->validate([
                    'images' => 'required',
                ]);
            }
            if ($request->post('type') === config('constants.Commissioner') || $request->post('type') === config('constants.Commisioned')) {
                $request->validate([
                    'username' => 'required_if:id,null',
                ]);
            }

            if (!empty($request->post('username'))) {
                $username = $request->post('username');

                if (!is_numeric($username)) {
                    $update_username = Username::updateOrCreate(
                        [
                            'username' => $username
                        ],
                        [
                            'user_id' => NULL,
                            'created_by' => auth()->id()
                        ]
                    );
                    $username = $update_username->id;
                }
            }
            /*
            $username = Username::updateOrCreate(
                        [
                            'username' => $username
                        ],
                        [
                            'user_id' => NULL,
                            'created_by' => auth()->id()
                        ]
                    );
            */

            // $drawnBy = ($postType === config('constants.Commissioner')) ? $username->id : NULL;
            // $commissionedBy = ($postType === config('constants.Commisioned')) ? $username->id : NULL;

            $getUsername = Username::select('id')->where('user_id', auth()->id())->latest()->first();

            if ($postType === config('constants.Commissioner')) {
                $drawnBy = $username ?? NULL;
                $commissionedBy = $getUsername->id  ?? NULL;
            } else if ($postType === config('constants.Commisioned')) {
                $drawnBy = $getUsername->id  ?? NULL;
                $commissionedBy = $username ?? NULL;
            } else if ($postType === config('constants.Artist')) {
                $drawnBy = $getUsername->id ?? NULL;
                $commissionedBy = NULL;
            }
            if (!empty($postId)) {
                if ($request->post('type') === config('constants.Commissioner')) {
                    $postData = $request->only('subject_id', 'medium_id', 'keywords', 'price', 'speed', 'communication', 'quality', 'professionalism');
                } elseif ($request->post('type') === config('constants.Commisioned')) {
                    $postData = $request->only('subject_id', 'medium_id', 'keywords', 'transaction', 'speed', 'communication', 'concept');
                } else {
                    $postData = $request->only('subject_id', 'medium_id', 'keywords');
                }
                $postData['keywords'] = implode(',', $request->post('keywords', [])) ?? NULL;
            } else {
                $postData = $request->post();
                $postData['drawn_by'] = $drawnBy;
                $postData['commisioned_by'] = $commissionedBy;
                $postData['user_id'] = auth()->id();
                $postData['keywords'] = implode(',', $request->post('keywords', [])) ?? NULL;
                $postData['keywords'] = str_replace('#', '', $postData['keywords']);
                $postData['status'] = 1;

                if ($postType === config('constants.Commissioner')) {
                    $postData['type_id'] = config('constants.commissioner');
                } else if ($postType === config('constants.Commisioned')) {
                    $postData['type_id'] = config('constants.commissioned');
                } else if ($postType === config('constants.Artist')) {
                    $postData['type_id'] = config('constants.artist');
                }
            }
            // dd($postData);
            if (isset($request->work_again)) {
                $postData['want_work_again'] = $request->work_again;
            }

            if ($request->hasFile('cover_image')) {
                // dd($request->all());
                $year = Carbon::now()->format('Y');
                $coverImage = $request->file('cover_image');

                $data = $request->post("crop_cover_image");
                $image_array_1 = explode(";", $data);
                $image_array_2 = explode(",", $image_array_1[1]);
                $data = base64_decode($image_array_2[1]);
                $imageName = (time() + 10) . '.' . $coverImage->getClientOriginalExtension();
                $coverImage->storeAs('posts/cover_images/' . $year . '/' . auth()->id(), $imageName);
                file_put_contents(storage_path('app/public/posts/cover_images/' . $year . '/' . auth()->id() . '/' . $imageName), $data);

                // $image_array_1 = explode(";", $coverImage);
                // $image_array_2 = explode(",", $image_array_1[1]);
                // $data = base64_decode($image_array_2[1]);


                // $month = Carbon::now()->format('m');
                // $fileName = (time() + 10) . '.' . $coverImage->getClientOriginalExtension();
                // $coverImage->storeAs('posts/cover_images/' . $year . '/' . auth()->id(), $fileName);
                $path = 'storage/posts/cover_images/' . $year . '/' . auth()->id() . '/' . $imageName;
                $postData['cover_image'] = $path;
            }
            // dd($request->file('images'));
            $post = Post::updateOrCreate(
                ['id' => $postId],
                $postData
            );

            if ($post) {
                if ($request->hasFile('images')) {
                    $year = Carbon::now()->format('Y');
                    $month = Carbon::now()->format('m');
                    $images = $request->file('images');
                    foreach ($images as $key => $image) {
                        $fileName = (time() + ($key * 10)) . '.' . $image->getClientOriginalExtension();
                        $image->storeAs('posts/' . $year . '/' . $month . '/' . auth()->id(), $fileName);
                        $path = 'storage/posts/' . $year . '/' . $month . '/' . auth()->id() . '/' . $fileName;
                        Image::create([
                            'post_id' => $post->id,
                            'image_path' => $path,
                            'display_order' => (Image::wherePostId($post->id)->count() + 1)
                        ]);
                    }
                }

                // session()->flash('success', 'Post created successfully.');

                // return redirect(url('posts/' . $post->id));
                return response()->json([
                    'status' => true,
                    'message' => 'Post created successfully.',
                    'id' => $post->id
                ]);
            }
        }

        // session()->flash('error', 'Something went wrong, Please try again.');

        // return redirect()->back();
        return response()->json([
            'status' => false,
            'message' => 'Something went wrong, Please try again.'
        ]);
    }

    public function show($id)
    {
        view()->share('page_title', 'Post Information');
        // $post = Post::with('images', 'drawnBy', 'commisionedBy', 'comments.user:id,display_name,profile_image', 'comments.replies')->find($id);

        $post = Post::with('images', 'drawnBy.user', 'commisionedBy.user')->find($id);

        $user_id = auth()->id() ?? NULL;
        $count = 0;

        $postview = PostView::updateOrCreate(
            [
                'post_id' => $id,
                'user_id' => $user_id,
            ],
            [
                'count' => DB::Raw('count+1'),
            ]
        );
        $post->views_count = PostView::where('post_id', $id)->sum('count') ?? 000;

        $post->post_like_by_user = Like::where('post_id', $id)->where('user_id', $user_id)->count() ?? 0;
        $post->drwan_by_follow = Follow::where('user_id', $user_id)->where('follow_user_id', $post->drawnBy->user_id)->count() ?? 0;
        if (!empty($post->commisionedBy->user_id))
            $post->commisioned_by_follow = Follow::where('user_id', $user_id)->where('follow_user_id', $post->commisionedBy->user_id)->count() ?? 0;

        $post->likes = Like::where('post_id', $id)->count() ?? 0;

        $getUsernames = Username::select('user_id')->where('id', $post->drawn_by)->first();
        if ($post->type_id == 2) {
            $post->type = config('constants.Commisioned');
        } else {
            $post->type = config('constants.Commissioner');
        }
        return view('frontend.posts.show', compact('post'));
    }

    public function update(Request $request, $id)
    {
        view()->share('page_title', 'Post Edit');

        $subjects = Subject::whereStatus(1)->get();
        $mediums = Medium::whereStatus(1)->get();
        $usernames = Username::where('user_id', '!=', auth()->user()->id)->get();
        $post = Post::with('images', 'drawnBy', 'commisionedBy')->find($id);
        $getUsernames = Username::select('user_id')->where('id', $post->drawn_by)->first();
        // if (empty($post->commisioned_by)) {
        //     $type = config('constants.Artist');
        // } else if ($post->user_id == $getUsernames->user_id) {
        //     $type = config('constants.Commisioned');
        // } else {
        //     $type = config('constants.Commissioner');
        // }

        if ($post->type_id == 1) {
            $type = config('constants.Artist');
        } elseif ($post->type_id == 2) {
            $type = config('constants.Commisioned');
        } else {
            $type = config('constants.Commissioner');
        }
        // dd($post->commisioned_by);

        return view('frontend.posts.edit', compact('post', 'type', 'subjects', 'mediums', 'usernames'));
    }

    public function imageDelete(Request $request)
    {
        if (!empty($request->post('image_id'))) {
            $image_id = $request->post('image_id');
            // $delete = Image::where('image_path', $request->post('image_path'))->delete();
            $image = Image::find($image_id);
            $delete = $image->delete();
            if ($delete) {
                Image::where('display_order', '>', $image->display_order)->update(['display_order' => DB::raw('display_order - 1')]);
                return response()->json([
                    'status' => true,
                    'message' => 'Image delete Successfully.',
                ]);
            }
        }

        return response()->json([
            'status' => false,
            'message' => 'Something went wrong, Please try again.'
        ]);
    }

    public function likes(Request $request)
    {
        $post_id = $request->post('postid');
        $user_id = auth()->id();
        $is_like = 0;

        if (Like::where('post_id', $post_id)->where('user_id', $user_id)->count() == 0) {
            $likes = Like::create([
                'post_id' => $post_id,
                'user_id' => $user_id,
            ]);
            $is_like = 1;
            $likes_count = Like::where('post_id', $post_id)->count() ?? 0;
        } else {
            $likes = Like::where('post_id', $post_id)->where('user_id', $user_id)->delete();
            $likes_count = Like::where('post_id', $post_id)->count() ?? 0;
        }

        if ($likes) {
            return response()->json([
                'status' => true,
                'message' => 'Like changes Successfully.',
                'is_like' => $is_like,
                'likes_count' => $likes_count
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Something went wrong, Please try again.'
        ]);
    }
    public function follow(Request $request)
    {
        $follow_user_id = $request->post('follow_user_id');
        $user_id = Auth::user()->id;
        $is_follow = 0;

        if (Follow::where('user_id', $user_id)->where('follow_user_id', $follow_user_id)->count() > 0) {
            $follow = Follow::where('user_id', $user_id)->where('follow_user_id', $follow_user_id)->delete();
        } else {
            $follow = Follow::create([
                'user_id' => $user_id,
                'follow_user_id' => $follow_user_id,
            ]);
            $is_follow = 1;
        }

        if ($follow) {
            return response()->json([
                'status' => true,
                'message' => 'Follow changes Successfully.',
                'is_follow' => $is_follow
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Something went wrong, Please try again.'
        ]);
    }

    public function imagesOrder(Request $request)
    {
        if ($request->ajax()) {
            // dd($request->all());
            $request->validate([
                'sorting_images' => 'required',
                // 'order_id' => 'required',
            ]);
            $sorting_images = json_decode($request->post('sorting_images'), true);
            $save = false;
            foreach ($sorting_images as $image) {
                $save = Image::find($image['image_id'])->update(['display_order' => $image['order_id']]);
            }
            if ($save) {
                return response()->json([
                    'status' => true,
                    'message' => 'Display order changed Successfully.',
                ]);
            }
        }
        return response()->json([
            'status' => false,
            'message' => 'Something went wrong, Please try again.'
        ]);
    }

    public function comment(Request $request)
    {
        /* if ($request->ajax()) {
            $request->validate([
                'post_id' => 'required',
                'comment' => 'required',
            ]);
            $comment = Comment::create([
                'post_id' => $request->post('post_id'),
                'user_id' => auth()->id(),
                'parent_id' => $request->post('parent_id') ?? 0,
                'comment' => $request->post('comment')
            ]);
            if ($comment) {
                return response()->json([
                    'status' => true,
                    'message' => 'Comment added Successfully.'
                ]);
            }
        }

        return response()->json([
            'status' => false,
            'message' => 'Something went wrong, Please try again.'
        ]); */
    }
}
