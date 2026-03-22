const BASE_URL = process.env.NEXT_PUBLIC_API_BASE_URL;

export type CreateTaskRequestResponse = {
    id: number;
    status: string;
    message: string;
};

export type GetTaskRequestResponse = {
    data: {
        id: number;
        status: string;
        generated_tasks?: unknown[];
        ai_execution_logs?: unknown[];
    };
};

export async function createTaskRequest(data: {
    goal: string;
    available_hours: number;
    previous_score: number;
    note?: string;
}): Promise<CreateTaskRequestResponse> {
    const res = await fetch(`${BASE_URL}/api/task-generation-requests`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
        },
        body: JSON.stringify(data),
    });

    const text = await res.text();

    let json: CreateTaskRequestResponse;
    try {
        json = JSON.parse(text);
    } catch {
        throw new Error(`JSONではないレスポンス: ${text}`);
    }

    if (!res.ok) {
        throw new Error(`API error: ${res.status} ${text}`);
    }

    return json;
}

export async function getTaskRequest(
    id: number
): Promise<GetTaskRequestResponse> {
    const res = await fetch(`${BASE_URL}/api/task-generation-requests/${id}`, {
        headers: {
            Accept: "application/json",
        },
    });

    const text = await res.text();

    let json: GetTaskRequestResponse;
    try {
        json = JSON.parse(text);
    } catch {
        throw new Error(`JSONではないレスポンス: ${text}`);
    }

    if (!res.ok) {
        throw new Error(`API error: ${res.status} ${text}`);
    }

    return json;
}
