<?php

namespace App\Http\Resources\Auth;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegisterResource extends JsonResource
{
    protected $message;

    /**
     * リソースインスタンスの生成
     *
     * @param  \App\Models\User  $user
     * @param  string  $message
     * @return void
     */
    public function __construct($user, $message = 'ユーザー登録が完了しました')
    {
        parent::__construct($user);
        $this->message = $message;
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
            'message' => $this->message,
            'user' => new UserResource($this->resource),
        ];
    }
}