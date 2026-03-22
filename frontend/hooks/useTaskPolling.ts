import { useEffect, useState } from "react";
import { getTaskRequest } from "@/lib/api";

export function useTaskPolling(requestId: number | null) {
    const [data, setData] = useState<any>(null);
    const [status, setStatus] = useState<string | null>(null);

    useEffect(() => {
        if (!requestId) return;

        const interval = setInterval(async () => {
            try {
                const res = await getTaskRequest(requestId);
                const s = res.data.status;

                setData(res.data);
                setStatus(s);

                if (s === "completed" || s === "failed") {
                    clearInterval(interval);
                }
            } catch (e) {
                console.error(e);
                clearInterval(interval);
            }
        }, 2000);

        return () => clearInterval(interval);
    }, [requestId]);

    return { data, status };
}
