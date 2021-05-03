<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\Medium;
use App\Models\Post;
use App\Models\Subject;
use App\Models\User;
use App\Models\Username;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        return view('frontend.posts.create', compact('type', 'subjects', 'mediums'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required',
//            'owner_name' => 'required_if:type,commissioner',
            'subject_id' => 'required',
            'medium_id' => 'required',
            'title' => 'required',
            'description' => 'required',
            // 'images' => 'required',
        ]);

        $postId = $request->post('id');
        $postType = $request->post('type');
        $drawnBy = NULL;
        $commissionedBy = NULL;
        if ($postType === 'commissioner') {
            $drawnBy = Username::where('username', $request->post('owner_name'))->first()->user_id ?? NULL;
        }
        else {
            $commissionedBy = User::where('display_name', $request->post('owner_name'))->first()->id ?? NULL;
        }

        $postData = $request->post();
        $postData['drawn_by'] = $drawnBy;
        $postData['commissioned_by'] = $commissionedBy;
        $postData['user_id'] = auth()->id();
        $postData['keywords'] = implode(',', $request->post('keywords')) ?? NULL;
        $postData['status'] = 1;

        $post = Post::updateOrCreate(
            ['id' => $postId],
            $postData
        );

        if ($post) {
            if ($request->hasFile('images')) {
                $images = $request->file('images');
                $year = Carbon::now()->format('Y');
                $month = Carbon::now()->format('m');
                foreach ($images as $key => $image) {
                    $fileName = ( time() + ( $key * 10 ) ) . '.' . $image->getClientOriginalExtension();
                    $image->storeAs('posts/' . $year . '/' . $month . '/' . auth()->id(), $fileName);
                    $path = 'storage/posts/' . $year . '/' . $month . '/' . auth()->id() .'/'. $fileName;
                    Image::create([
                        'post_id' => $post->id,
                        'image_path' => $path
                    ]);
                }
            }

            session()->flash('success', 'Post created successfully.');

            return redirect(url('posts/' . $post->id));
        }

        session()->flash('error', 'Something went wrong, Please try again.');

        return redirect()->back();
    }

    public function show($id)
    {
        view()->share('page_title', 'Post Information');
        $post = Post::with('images')->find($id);

        return view('frontend.posts.show', compact('post'));
    }
}
