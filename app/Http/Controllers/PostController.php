<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http; // using Http laravel component besides guzzle library
use Illuminate\Support\Facades\Log;
use Cache;
use Carbon\Carbon;

class PostController extends Controller
{
    public function list(){
        try {
            $cacheKey = 'post';
            $posts = [];

            if ($posts = Cache::get($cacheKey)) {
                return view('posts/list', compact('posts'));
            }

            $url = config('services.api_url.posts');
            $response = Http::get($url);

            if ($response->failed()) {
                Log::error($response->body());
                $response->throw();
            }
            
            $data = $response->json();

            foreach($data as $dt){
                $posts[$dt['id']] = $dt;
            }

            $url = config('services.api_url.comments');
            $response = Http::get($url);

            if ($response->failed()) {
                Log::error($response->body());
                $response->throw();
            }
            
            $comments = $response->json();

            foreach($comments as $dt){
                $posts[$dt['postId']]['comments'][] = $dt;
            }

            $expiresAt = Carbon::now()->endOfDay()->addSecond();
            Cache::put($cacheKey, $posts, $expiresAt);

            return view('posts/list', compact('posts'));
        } catch (\Exception $e) {
            Log::error($e);
            throw new \Exception($e, 500);
        }
    }
}