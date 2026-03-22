"use client";

import React, { useState } from "react";
import { createTaskRequest } from "@/lib/api";
import { useTaskPolling } from "@/hooks/useTaskPolling";

type FormState = {
    goal: string;
    available_hours: number;
    previous_score: number;
    note: string;
};

type GeneratedTask = {
    id?: number;
    title?: string;
    description?: string;
};

type AiExecutionLog = {
    id?: number;
    status?: string;
    message?: string;
    raw_response?: unknown;
};

function formatUnknown(value: unknown): string {
    if (typeof value === "string") return value;
    try {
        return JSON.stringify(value, null, 2);
    } catch {
        return String(value);
    }
}

export default function Home() {
    const [form, setForm] = useState<FormState>({
        goal: "",
        available_hours: 1,
        previous_score: 0,
        note: "",
    });

    const [requestId, setRequestId] = useState<number | null>(null);
    const [submitError, setSubmitError] = useState<string | null>(null);
    const [isSubmitting, setIsSubmitting] = useState(false);

    const { data, status } = useTaskPolling(requestId);

    const generatedTasks: GeneratedTask[] = Array.isArray(data?.generated_tasks)
        ? (data.generated_tasks as GeneratedTask[])
        : [];

    const aiExecutionLogs: AiExecutionLog[] = Array.isArray(data?.ai_execution_logs)
        ? (data.ai_execution_logs as AiExecutionLog[])
        : [];

    const handleSubmit = async () => {
        setSubmitError(null);
        setIsSubmitting(true);

        try {
            const res = await createTaskRequest(form);
            console.log("POST response:", res);

            const id = res?.id;

            if (typeof id !== "number") {
                setSubmitError("response.id が取得できません");
                return;
            }

            setRequestId(id);
        } catch (error) {
            console.error("handleSubmit error:", error);

            if (error instanceof Error) {
                setSubmitError(error.message);
            } else {
                setSubmitError("不明なエラーが発生しました");
            }
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <main className="mx-auto max-w-2xl space-y-6 p-6">
            <h1 className="text-2xl font-bold">AIタスク生成</h1>

            <div className="space-y-4 rounded border p-4">
                <div className="space-y-2">
                    <label className="block text-sm font-medium">目標</label>
                    <input
                        className="w-full rounded border p-2"
                        placeholder="例: 英語学習を習慣化したい"
                        value={form.goal}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                            setForm({ ...form, goal: e.target.value })
                        }
                    />
                </div>

                <div className="space-y-2">
                    <label className="block text-sm font-medium">使える時間（時間）</label>
                    <input
                        type="number"
                        className="w-full rounded border p-2"
                        value={form.available_hours}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                            setForm({
                                ...form,
                                available_hours: Number(e.target.value),
                            })
                        }
                    />
                </div>

                <div className="space-y-2">
                    <label className="block text-sm font-medium">前回スコア</label>
                    <input
                        type="number"
                        className="w-full rounded border p-2"
                        value={form.previous_score}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                            setForm({
                                ...form,
                                previous_score: Number(e.target.value),
                            })
                        }
                    />
                </div>

                <div className="space-y-2">
                    <label className="block text-sm font-medium">補足メモ</label>
                    <textarea
                        className="w-full rounded border p-2"
                        rows={4}
                        placeholder="例: 平日は夜しか時間が取れない"
                        value={form.note}
                        onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) =>
                            setForm({ ...form, note: e.target.value })
                        }
                    />
                </div>

                <button
                    type="button"
                    onClick={handleSubmit}
                    disabled={isSubmitting}
                    className="rounded bg-blue-600 px-4 py-2 text-white disabled:opacity-50"
                >
                    {isSubmitting ? "送信中..." : "生成する"}
                </button>

                {submitError !== null && (
                    <p className="text-sm text-red-600">送信エラー: {submitError}</p>
                )}
            </div>

            <div className="space-y-3 rounded border p-4">
                <h2 className="text-lg font-semibold">ステータス</h2>

                {requestId !== null ? (
                    <p>Request ID: {requestId}</p>
                ) : (
                    <p>まだリクエストは送信されていません。</p>
                )}

                {status === "pending" && <p>受付中...</p>}
                {status === "processing" && <p>生成中...</p>}
                {status === "failed" && (
                    <p className="text-red-600">生成に失敗しました。</p>
                )}
                {status === "completed" && (
                    <p className="text-green-600">生成が完了しました。</p>
                )}
            </div>

            {status === "completed" && (
                <div className="space-y-3 rounded border p-4">
                    <h2 className="text-xl font-semibold">生成タスク</h2>

                    {generatedTasks.length === 0 ? (
                        <p>生成タスクはまだありません。</p>
                    ) : (
                        <div className="space-y-2">
                            {generatedTasks.map((task, index) => (
                                <div key={task.id ?? index} className="rounded border p-3">
                                    <p className="font-medium">
                                        {task.title ?? `タスク ${index + 1}`}
                                    </p>

                                    {task.description ? (
                                        <p className="mt-1 text-sm text-gray-600">
                                            {task.description}
                                        </p>
                                    ) : null}
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            )}

            {aiExecutionLogs.length > 0 && (
                <div className="space-y-3 rounded border p-4">
                    <h2 className="text-xl font-semibold">AI実行ログ</h2>

                    <div className="space-y-2">
                        {aiExecutionLogs.map((log, index) => (
                            <div key={log.id ?? index} className="rounded border p-3">
                                <p className="font-medium">{log.status ?? `log ${index + 1}`}</p>

                                {log.message ? (
                                    <p className="mt-1 text-sm text-gray-600">{log.message}</p>
                                ) : null}

                                {log.raw_response !== undefined ? (
                                    <details className="mt-2">
                                        <summary className="cursor-pointer text-sm">
                                            raw_response を表示
                                        </summary>
                                        <pre className="mt-2 overflow-x-auto text-xs">
                      {formatUnknown(log.raw_response)}
                    </pre>
                                    </details>
                                ) : null}
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </main>
    );
}
