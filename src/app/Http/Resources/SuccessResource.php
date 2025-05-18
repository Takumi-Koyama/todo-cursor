<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SuccessResource extends JsonResource
{
    protected $message;

    /**
     * リソースインスタンスの生成
     *
     * @param  mixed  $resource
     * @param  string  $message
     * @return void
     */
    public function __construct($resource = null, $message = '操作が成功しました')
    {
        parent::__construct($resource);
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
        ];
    }
} 