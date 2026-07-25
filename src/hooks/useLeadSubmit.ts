"use client";

import { useCallback, useRef, useState } from "react";
import { useRouter } from "next/navigation";
import { trackGoal } from "@/lib/analytics";

export type SubmitStatus = "idle" | "loading" | "success" | "error";

export function useLeadSubmit() {
  const router = useRouter();
  const [status, setStatus] = useState<SubmitStatus>("idle");
  const [errorMessage, setErrorMessage] = useState("");
  const [renderedAt] = useState(() => Date.now());
  const submittedRef = useRef(false);

  const submit = useCallback(
    async (formData: FormData) => {
      if (submittedRef.current) return;

      setStatus("loading");
      setErrorMessage("");
      formData.set("formRenderedAt", String(renderedAt));

      try {
        const response = await fetch("/api/lead", { method: "POST", body: formData });
        const data: { ok: boolean; message?: string } = await response.json();

        if (!response.ok || !data.ok) {
          setStatus("error");
          setErrorMessage(data.message || "Не удалось отправить заявку. Попробуйте ещё раз.");
          return;
        }

        submittedRef.current = true;
        setStatus("success");
        trackGoal("lead_submit", { source: String(formData.get("source") ?? "") });
        router.push("/spasibo");
      } catch {
        setStatus("error");
        setErrorMessage("Ошибка сети. Проверьте подключение к интернету и попробуйте снова.");
      }
    },
    [router, renderedAt],
  );

  return { status, errorMessage, submit, isSubmitting: status === "loading" };
}
