<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiExecutionLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'model' => $this->model,
            'prompt_version' => $this->prompt_version,
            'execution_type' => $this->execution_type,
            'retry_count' => $this->retry_count,
            'status' => $this->status,
            'error_message' => $this->error_message,
            'latency_ms' => $this->latency_ms,
            'prompt_tokens' => $this->prompt_tokens,
            'completion_tokens' => $this->completion_tokens,
            'total_tokens' => $this->total_tokens,
            'cost_usd' => $this->cost_usd,
            'executed_at' => $this->executed_at,
            'request_payload' => $this->request_payload,
            'response_payload' => $this->response_payload,
            'raw_response' => $this->raw_response,
        ];
    }
}
