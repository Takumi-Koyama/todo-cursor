<?php

namespace App\Http\Resources\Auth;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthTokenResource extends JsonResource
{
    protected $token;
    protected $expiresIn;
    
    /**
     * リソースインスタンスの生成
     *
     * @param  \App\Models\User  $user
     * @param  string  $token
     * @param  int  $expiresIn
     * @return void
     */
    public function __construct($user, $token, $expiresIn)
    {
        parent::__construct($user);
        $this->token = $token;
        $this->expiresIn = $expiresIn;
    }

    /**
     * リソースを配列に変換
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'access_token' => $this->token,
            'token_type' => 'Bearer',
            'expires_in' => $this->expiresIn,
            'user' => new UserResource($this->resource),
        ];
    }
} 